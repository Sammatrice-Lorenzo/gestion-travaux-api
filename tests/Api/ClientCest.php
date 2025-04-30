<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class ClientCest
{
    private const string FIRST_NAME_CLIENT = 'Firstname Client Test';

    private const string UPDATED_POSTAL_CODE = '75002';

    private const string DEFAULT_PHONE_NUMBER = '0123456789';

    private int $clientId;

    private User $user;

    public function _before(ApiTester $I): void
    {
        $I->loginAs('user@test.com', '1234');

        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => 'user@test.com']);
        $this->user = $user;
        $I->amOnPage('/api');
    }

    public function testCreateClient(ApiTester $I): void
    {
        $parameters = $this->getParameters('75001', self::DEFAULT_PHONE_NUMBER);
        $I->sendPost('/api/clients', $parameters);
        
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);
        $this->clientId = $I->grabDataFromResponseByJsonPath('id')[0];
        
        $parametersWithErrors = $this->getParameters('75001015', 'abc025460');
        $I->sendPost('/api/clients', $parametersWithErrors);
        $I->seeResponseCodeIsClientError();
    }

    #[Depends('testCreateClient')]
    public function testPutClientByUser(ApiTester $I): void
    {
        $parameters = $this->getParameters(self::UPDATED_POSTAL_CODE, self::DEFAULT_PHONE_NUMBER);

        $I->sendPut("/api/clients/{$this->clientId}", $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);
    }

    #[Depends('testPutClientByUser')]
    public function testGetClient(ApiTester $I): void
    {
        $I->sendGet("/api/clients/{$this->clientId}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($this->getParameters(self::UPDATED_POSTAL_CODE, self::DEFAULT_PHONE_NUMBER));
    }

    #[Depends('testCreateClient')]
    public function testGetCollectionClientByUser(ApiTester $I): void
    {
        $response = $I->sendGet('/api/clients');
        $I->seeResponseCodeIsSuccessful();

        $clients = json_decode($response);
        foreach ($clients as $client) {
            $I->assertEquals($client->user->id, $this->user->getId());
        }
    }

    #[Depends('testGetClient')]
    public function testDeleteClientByUser(ApiTester $I): void
    {
        $I->sendDelete("/api/clients/{$this->clientId}");
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * @return array<string, string>
     */
    private function getParameters(string $postalCode, string $phoneNumber): array
    {
        return [
            'firstname' => self::FIRST_NAME_CLIENT,
            'lastname' => 'Lastname Client Test',
            'phoneNumber' => $phoneNumber,
            'postalCode' => $postalCode,
            'city' => 'Paris',
            'streetAddress' => '85 rue Paris',
        ];
    }
}
