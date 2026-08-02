<?php

declare(strict_types=1);

namespace App\Tests\Api;

use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser;
use App\Helper\DateFormatHelper;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class WorkEventDaySearchCest
{
    private const string URL_API = '/api/work_event_days';

    private const string TITLE_MATCHING = 'Peinture salon Dupont';

    private const string TITLE_NOT_MATCHING = 'Réparation toiture';

    private int $matchingEventId;

    private int $notMatchingEventId;

    private int $clientId;

    public function _before(ApiTester $I): void
    {
        $I->loginAs();
    }

    public function testCreateFixturesForSearch(ApiTester $I): void
    {
        $I->sendPost('/api/clients', [
            'firstname' => 'Firstname Search Test',
            'lastname' => 'Lastname Search Test',
            'email' => 'client-search@test.com',
            'phoneNumber' => '0123456789',
            'postalCode' => '75001',
            'city' => 'Paris',
            'streetAddress' => '85 rue Paris',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $this->clientId = $I->grabDataFromResponseByJsonPath('id')[0];

        $defaultFormatWithTime = DateFormatHelper::DEFAULT_FORMAT_WITH_TIME;

        $I->sendPost(self::URL_API, [
            'title' => self::TITLE_MATCHING,
            'startDate' => (new DateTime('08:00:00'))->format($defaultFormatWithTime),
            'endDate' => (new DateTime('12:00:00'))->format($defaultFormatWithTime),
            'color' => '#FFFF',
            'client' => "/api/clients/{$this->clientId}",
        ]);
        $I->seeResponseCodeIsSuccessful();
        $this->matchingEventId = $I->grabDataFromResponseByJsonPath('id')[0];

        $I->sendPost(self::URL_API, [
            'title' => self::TITLE_NOT_MATCHING,
            'startDate' => (new DateTime('08:00:00'))->format($defaultFormatWithTime),
            'endDate' => (new DateTime('12:00:00'))->format($defaultFormatWithTime),
            'color' => '#FFFF',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $this->notMatchingEventId = $I->grabDataFromResponseByJsonPath('id')[0];
    }

    #[Depends('testCreateFixturesForSearch')]
    public function testSearchMatchesRegexTitle(ApiTester $I): void
    {
        $I->sendPost(self::URL_API . '/search', $this->searchParameters('^Peinture'));
        $I->seeResponseCodeIsSuccessful();

        $titles = $I->grabDataFromResponseByJsonPath('$.*.title');
        $I->assertContains(self::TITLE_MATCHING, $titles);
        $I->assertNotContains(self::TITLE_NOT_MATCHING, $titles);
    }

    #[Depends('testSearchMatchesRegexTitle')]
    public function testSearchFiltersByClientAndDateRange(ApiTester $I): void
    {
        $parameters = $this->searchParameters('.*');
        $parameters['client'] = $this->clientId;
        $I->sendPost(self::URL_API . '/search', $parameters);
        $I->seeResponseCodeIsSuccessful();

        $titles = $I->grabDataFromResponseByJsonPath('$.*.title');
        $I->assertContains(self::TITLE_MATCHING, $titles);
        $I->assertNotContains(self::TITLE_NOT_MATCHING, $titles);

        $outOfRangeParameters = $this->searchParameters('.*', -10, -5);
        $I->sendPost(self::URL_API . '/search', $outOfRangeParameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseEquals('[]');
    }

    #[Depends('testSearchFiltersByClientAndDateRange')]
    public function testSearchInvalidRegexReturns422(ApiTester $I): void
    {
        $I->sendPost(self::URL_API . '/search', $this->searchParameters('['));
        $I->seeResponseCodeIs(422);

        $response = json_decode((string) $I->grabResponse(), true);
        $I->assertArrayHasKey('hydra:description', $response);
    }

    #[Depends('testSearchInvalidRegexReturns422')]
    public function testSearchExportPdf(ApiTester $I): void
    {
        $fileName = 'workEventDaySearchExportTest.pdf';
        $parameters = $this->searchParameters('^Peinture');
        $parameters['format'] = 'pdf';

        $I->sendPost(self::URL_API . '/search/export', $parameters);
        $I->seeResponseCodeIsSuccessful();

        $file = $I->grabResponse();
        $filePath = $I->createFile($fileName, $file);

        $text = preg_replace("/\r|\n|\t/", ' ', (new Parser())->parseFile(realpath($filePath))->getText());
        $I->assertNotFalse(strpos($text, self::TITLE_MATCHING));

        $I->removeFile($fileName);
    }

    #[Depends('testSearchExportPdf')]
    public function testSearchExportXlsx(ApiTester $I): void
    {
        $fileName = 'workEventDaySearchExportTest.xlsx';
        $parameters = $this->searchParameters('^Peinture');
        $parameters['format'] = 'xlsx';

        $I->sendPost(self::URL_API . '/search/export', $parameters);
        $I->seeResponseCodeIsSuccessful();

        $file = $I->grabResponse();
        $filePath = $I->createFile($fileName, $file);

        $spreadsheet = IOFactory::load(realpath($filePath));
        $sheet = $spreadsheet->getActiveSheet();
        $cellValues = [];
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cellValues[] = $cell->getValue();
            }
        }

        $I->assertContains(self::TITLE_MATCHING, $cellValues);

        $I->removeFile($fileName);
    }

    #[Depends('testSearchExportXlsx')]
    public function testDeleteWorkEventDaySearchFixtures(ApiTester $I): void
    {
        $I->sendDelete(self::URL_API . "/{$this->matchingEventId}");
        $I->seeResponseCodeIsSuccessful();
        $I->sendDelete(self::URL_API . "/{$this->notMatchingEventId}");
        $I->seeResponseCodeIsSuccessful();
        $I->sendDelete("/api/clients/{$this->clientId}");
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * @return array<string, int|string>
     */
    private function searchParameters(string $search, int $startDayOffset = -1, int $endDayOffset = 1): array
    {
        $format = DateFormatHelper::DEFAULT_FORMAT;

        return [
            'search' => $search,
            'startDate' => (new DateTime("{$startDayOffset} days"))->format($format),
            'endDate' => (new DateTime("{$endDayOffset} days"))->format($format),
        ];
    }
}
