<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Support\FunctionalTester;

final class MessengerMonitorControllerCest
{
    private const string DASHBOARD_URL = '/admin/messenger';

    public function testEveryPageIsAvailableWithoutLogin(FunctionalTester $I): void
    {
        foreach (['', '/history', '/statistics', '/schedule', '/transport'] as $page) {
            $I->amOnPage(self::DASHBOARD_URL . $page);
            $I->seeResponseCodeIs(200);
        }
    }

    public function testDashboardIsNotUnderApi(FunctionalTester $I): void
    {
        // Le nginx du front route /api vers l'application sur le port 443 : le tableau de bord ne doit pas s'y trouver.
        $I->amOnPage('/api' . self::DASHBOARD_URL);
        $I->seeResponseCodeIs(404);
    }
}
