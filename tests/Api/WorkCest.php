<?php

declare(strict_types=1);

namespace App\Tests\Api;

use DateTime;
use DateInterval;
use App\Entity\User;
use App\Entity\Client;
use App\Enum\ProgressionEnum;
use App\Helper\DateFormatHelper;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class WorkCest
{
    private const string URL_API = '/api/works';

    private const string START_TIME_FORMAT = '08:00:00';

    private const string END_TIME_FORMAT = '18:00:00';

    private User $user;

    private Client $client;

    private int $workId;

    public function _before(ApiTester $I): void
    {
        $I->loginAs();

        /** @var User $user */
        $user = $I->grabEntity(User::class, ['email' => 'user@test.com']);
        $this->user = $user;

        /** @var Client $client */
        $client = $I->grabEntity(Client::class, ['user' => $user]);
        $this->client = $client;

        $I->amOnPage('/api');
    }

    public function testCreateWork(ApiTester $I): void
    {
        $start = new DateTime(self::START_TIME_FORMAT);
        $end = new DateTime(self::END_TIME_FORMAT);

        $parameters = $this->getParameters($start, $end, 127);
        $I->sendPost(self::URL_API, $parameters);
        $parametersAsserts = $this->getParametersAsserts($start, $end, 127);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parametersAsserts);
        $this->workId = $I->grabDataFromResponseByJsonPath('id')[0];

        $parametersWithErrors = $this->getParameters(start: $start, end: $end, totalAmount: -5);

        $I->sendPost(self::URL_API, $parametersWithErrors);
        $I->seeResponseCodeIsClientError();
    }

    #[Depends('testCreateWork')]
    public function testPutWorkByUser(ApiTester $I): void
    {
        [$start, $end] = $this->getDatesApiPut();

        $parameters = $this->getParameters($start, $end, 1000);
        $I->sendPut(self::URL_API . "/{$this->workId}", $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($this->getParametersPut());
    }

    #[Depends('testPutWorkByUser')]
    public function testGetWork(ApiTester $I): void
    {
        $I->sendGet(self::URL_API . "/{$this->workId}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($this->getParametersPut());
    }

    #[Depends('testCreateWork')]
    public function testGetCollectionWorkByUser(ApiTester $I): void
    {
        $response = $I->sendGet(self::URL_API);
        $I->seeResponseCodeIsSuccessful();

        $works = json_decode($response);
        foreach ($works as $work) {
            $I->assertEquals($work->user->id, $this->user->getId());
        }
    }

    #[Depends('testGetWork')]
    public function testDeleteWorkByUser(ApiTester $I): void
    {
        $I->sendDelete(self::URL_API . "/{$this->workId}");
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * @return array<string, float|string>
     */
    private function getParameters(DateTime $start, DateTime $end, float $totalAmount): array
    {
        return [
            'name' => 'Test work api',
            'city' => 'Paris',
            'start' => $start->format(DateFormatHelper::DEFAULT_FORMAT_WITH_TIME),
            'end' => $end->format(DateFormatHelper::DEFAULT_FORMAT_WITH_TIME),
            'progression' => ProgressionEnum::IN_PROGRESS->value,
            'equipements' => [],
            'totalAmount' => $totalAmount,
            'client' => "/api/clients/{$this->client->getId()}",
        ];
    }

    /**
     * @return array<string, float|string>
     */
    private function getParametersAsserts(DateTime $start, DateTime $end, float $totalAmount): array
    {
        return [
            'name' => 'Test work api',
            'city' => 'Paris',
            'start' => $start->format('c'),
            'end' => $end->format('c'),
            'progression' => ProgressionEnum::IN_PROGRESS->value,
            'totalAmount' => $totalAmount,
            'client' => [
                'id' => $this->client->getId(),
                'firstname' => $this->client->getFirstname(),
                'lastname' => $this->client->getLastname(),
                'phoneNumber' => $this->client->getPhoneNumber(),
                'postalCode' => $this->client->getPostalCode(),
                'city' => $this->client->getCity(),
                'streetAddress' => $this->client->getStreetAddress(),
                'name' => $this->client->getName(),
                'user' => ['id' => $this->user->getId()],
            ],
            'equipements' => [],
        ];
    }

    /**
     * @return DateTime[]
     */
    private function getDatesApiPut(): array
    {
        $start = (new DateTime(self::START_TIME_FORMAT))->sub(new DateInterval('P1D'));
        $end = (new DateTime(self::END_TIME_FORMAT))->add(new DateInterval('P1D'));

        return [$start, $end];
    }

    /**
     * @return array<string, float|string>
     */
    private function getParametersPut(): array
    {
        [$start, $end] = $this->getDatesApiPut();

        return $this->getParametersAsserts($start, $end, 1000);
    }
}
