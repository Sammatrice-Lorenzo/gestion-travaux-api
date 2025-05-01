<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserCest
{
    private const string PASSWORD = 'strongPassword%';

    private User $user;

    public function _before(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => 'user@test.com']);
        $this->user = $user;
        $I->amOnPage('/api');
    }

    public function testGetUser(ApiTester $I): void
    {
        $I->loginAs();

        $I->sendGet('/api/user');
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'id' => $this->user->getId(),
            'firstname' => $this->user->getFirstname(),
            'lastname' => $this->user->getLastname(),
            'email' => $this->user->getEmail(),
        ]);
    }

    public function testCreateUser(ApiTester $I): void
    {
        $parameters = $this->getParametersCreationUser('john.doe@test.com', self::PASSWORD, self::PASSWORD);
        $I->sendPost('/api/register', $parameters);
        $I->seeResponseCodeIsSuccessful();

        $this->assertConstraintUniqueEmailInCreation($I);
        $this->assertConstrainPasswordCreation($I);
    }

    public function testPutUser(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['id' => 2]);
        $I->loginAs($user->getEmail());

        $parameters = [
            'firstname' => 'Firstname test update user',
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
        ];

        $I->sendPut("/api/users/{$user->getId()}", $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);

        $this->assertConstraintUniqueEmailInPut($user, $I);
    }

    private function assertUnprocessableEntity(ApiTester $I): void
    {
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseCodeIsClientError();
    }

    private function assertConstraintUniqueEmailInPut(User $user, ApiTester $I): void
    {
        $I->sendPut("/api/users/{$user->getId()}", [
            'email' => 'user@test.com',
        ]);
        $this->assertUnprocessableEntity($I);
    }

    private function assertConstraintUniqueEmailInCreation(ApiTester $I): void
    {
        $parameters = $this->getParametersCreationUser('user@test.com', self::PASSWORD, self::PASSWORD);
        $I->sendPost('/api/register', $parameters);

        $I->seeResponseContainsJson([
            'detail' => 'Cette adresse email est déjà utilisée.',
        ]);
        $this->assertUnprocessableEntity($I);
    }

    private function assertConstrainPasswordCreation(ApiTester $I): void
    {
        $parameters = $this->getParametersCreationUser('aa@test.com', self::PASSWORD, 'ASAZSZEASAZSS');
        $I->sendPost('/api/register', $parameters);

        $I->seeResponseContainsJson([
            'detail' => 'confirmPassword: Le deux mots de passes ne correspondants pas !',
        ]);
        $this->assertUnprocessableEntity($I);
    }

    /**
     * @return array<string, string>
     */
    private function getParametersCreationUser(string $email, string $password, string $confirmPassword): array
    {
        return [
            'firstname' => 'Test création name',
            'lastname' => 'Test création lastname',
            'email' => $email,
            'password' => $password,
            'confirmPassword' => $confirmPassword,
        ];
    }
}
