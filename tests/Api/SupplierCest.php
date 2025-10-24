<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use App\Tests\Enum\UserFixturesEnum;

final class SupplierCest
{
    private const string BASE_URL = '/api/suppliers';

    private const string NAME_SUPPLIER = 'Supplier Test API';

    private const string UPDATED_PHONE_NUMBER = '+33 1 23 45 76 98';

    private const string DEFAULT_PHONE_NUMBER = '+33 1 23 45 67 89';

    private int $supplierId;

    private User $user;

    public function _before(ApiTester $I): void
    {
        $I->loginAs();

        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
    }

    public function testCreateSupplier(ApiTester $I): void
    {
        $parameters = $this->getParameters(self::DEFAULT_PHONE_NUMBER);

        $I->sendPost(self::BASE_URL, $parameters);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);
        $this->supplierId = $I->grabDataFromResponseByJsonPath('id')[0];

        $parametersWithErrors = $this->getParameters('abc025460');
        $I->sendPost(self::BASE_URL, $parametersWithErrors);
        $I->seeResponseCodeIsClientError();
    }

    #[Depends('testCreateSupplier')]
    public function testPutSupplierByUser(ApiTester $I): void
    {
        $parameters = $this->getParameters(self::UPDATED_PHONE_NUMBER);

        $I->sendPut(self::BASE_URL . "/{$this->supplierId}", $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);
    }

    #[Depends('testPutSupplierByUser')]
    public function testGetSupplier(ApiTester $I): void
    {
        $I->sendGet(self::BASE_URL . "/{$this->supplierId}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($this->getParameters(self::UPDATED_PHONE_NUMBER));
    }

    #[Depends('testCreateSupplier')]
    public function testGetCollectionSuppliersByUser(ApiTester $I): void
    {
        $response = $I->sendGet(self::BASE_URL);
        $I->seeResponseCodeIsSuccessful();

        $suppliers = json_decode($response);
        foreach ($suppliers as $supplier) {
            $I->assertEquals($supplier->user->id, $this->user->getId());
        }
    }

    #[Depends('testGetSupplier')]
    public function testDeleteSupplierByUser(ApiTester $I): void
    {
        $I->sendDelete(self::BASE_URL . "/{$this->supplierId}");
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * @return array<string, string>
     */
    private function getParameters(string $phoneNumber): array
    {
        return [
            'name' => self::NAME_SUPPLIER,
            'address' => '85 rue Paris',
            'city' => 'Paris',
            'country' => 'France',
            'phone' => $phoneNumber,
        ];
    }
}
