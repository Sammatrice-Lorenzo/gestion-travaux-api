<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Work;
use App\Enum\ProgressionEnum;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use DateTime;

final class InvoiceTest extends Unit
{
    protected UnitTester $tester;

    private User $user;

    private Client $client;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        /** @var Client $client */
        $client = $this->tester->grabEntity(Client::class);

        $this->user = $user;
        $this->client = $client;
    }

    public function testWorkInvoiceBidirectionalRelation(): void
    {
        $work = (new Work())
            ->setName('Work invoice test')
            ->setStart(new DateTime())
            ->setProgression(ProgressionEnum::IN_PROGRESS->value)
            ->setCity('Paris')
            ->setClient($this->client)
            ->setUser($this->user)
        ;

        $invoice = (new Invoice())
            ->setTitle('Invoice title test')
            ->setWork($work)
        ;

        $work->setInvoice($invoice);

        $this->tester->assertSame($work, $invoice->getWork());
        $this->tester->assertSame($invoice, $work->getInvoice());
    }
}
