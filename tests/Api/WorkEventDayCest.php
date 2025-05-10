<?php

declare(strict_types=1);

namespace App\Tests\Api;

use DateTime;
use App\Entity\User;
use App\Helper\DateHelper;
use Smalot\PdfParser\Parser;
use App\Helper\DateFormatHelper;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class WorkEventDayCest
{
    private const string URL_API = '/api/work_event_days';

    private const string TITLE_EVENT_DAY = 'Test work event day';

    private const string COLOR = '#a60202';

    private int $workEventId;

    private User $user;

    public function _before(ApiTester $I): void
    {
        $I->loginAs();

        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => 'user@test.com']);
        $this->user = $user;
    }

    public function testCreateWorkEventDay(ApiTester $I): void
    {
        $defautlFormatWithTime = DateFormatHelper::DEFAULT_FORMAT_WITH_TIME;
        $I->sendPost(self::URL_API, [
            'title' => self::TITLE_EVENT_DAY,
            'startDate' => (new DateTime('08:00:00'))->format($defautlFormatWithTime),
            'endDate' => (new DateTime('18:00:00'))->format($defautlFormatWithTime),
            'color' => '#FFFF',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['title' => self::TITLE_EVENT_DAY]);
        $this->workEventId = $I->grabDataFromResponseByJsonPath('id')[0];
    }

    #[Depends('testCreateWorkEventDay')]
    public function testPutWorkEventDayByUser(ApiTester $I): void
    {
        $parameterColor = ['color' => self::COLOR];
        $I->sendPut(self::URL_API . "/{$this->workEventId}", $parameterColor);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameterColor);
    }

    #[Depends('testPutWorkEventDayByUser')]
    public function testGetWorkEventDay(ApiTester $I): void
    {
        $I->sendGet(self::URL_API . "/{$this->workEventId}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['title' => self::TITLE_EVENT_DAY, 'color' => self::COLOR]);
    }

    #[Depends('testCreateWorkEventDay')]
    public function testGetCollectionWorkEventDayByUser(ApiTester $I): void
    {
        $response = $I->sendGet(self::URL_API);
        $I->seeResponseCodeIsSuccessful();

        $workEventDays = json_decode($response);
        foreach ($workEventDays as $workEventDay) {
            $I->assertEquals($workEventDay->user->id, $this->user->getId());
        }
    }

    #[Depends('testGetWorkEventDay')]
    public function testPostWorkEventDayDownloadFile(ApiTester $I): void
    {
        $fileName = 'workEventDayTest.pdf';
        $date = new DateTime();
        $I->sendPost(self::URL_API . '/file_download', [
            'date' => $date->format(DateFormatHelper::DEFAULT_FORMAT),
        ]);
        $I->seeResponseCodeIsSuccessful();
        $file = $I->grabResponse();
        $fileTest = $I->createFile($fileName, $file);

        $text = preg_replace("/\r|\n|\t/", ' ', (new Parser())->parseFile(realpath($fileTest))->getText());

        $assertFormatDate = DateHelper::FRENCH_MONTHS[(string) $date->format(DateFormatHelper::MONTH_FORMAT)];
        $I->assertNotFalse(strpos($text, $assertFormatDate));
        $I->assertNotFalse(strpos($text, self::TITLE_EVENT_DAY));

        $I->removeFile($fileName);
    }

    #[Depends('testPostWorkEventDayDownloadFile')]
    public function testDeleteWorkEventDayByUser(ApiTester $I): void
    {
        $I->sendDelete(self::URL_API . "/{$this->workEventId}");
        $I->seeResponseCodeIsSuccessful();
    }
}
