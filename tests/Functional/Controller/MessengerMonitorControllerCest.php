<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\ProcessedMessage;
use App\Message\SyncSupplierInvoicesMessage;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Exception\RunCommandFailedException;
use Symfony\Component\Console\Messenger\RunCommandContext;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Zenstruck\Messenger\Monitor\Stamp\MonitorStamp;

final class MessengerMonitorControllerCest
{
    private const string DASHBOARD_URL = '/admin/messenger';

    private const string REPORT = "Créées : 1, mises à jour : 0, ignorées : 0\n\nSociété Générale : créées 1, mises à jour 0, ignorées 0, en échec 0";

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

    public function testActionsAreRefusedByTheApplication(FunctionalTester $I): void
    {
        foreach (['/schedules/default/trigger/id/async', '/transport/failed/1/retry', '/transport/failed/1/remove'] as $action) {
            $I->sendAjaxPostRequest(self::DASHBOARD_URL . $action);
            $I->seeResponseCodeIs(401);
        }
    }

    public function testSuccessfulRunIsRecordedWithReadableReport(FunctionalTester $I): void
    {
        $envelope = $this->receivedEnvelope(new SyncSupplierInvoicesMessage(1))
            ->with(new HandledStamp(['status' => 'completed', 'report' => self::REPORT], 'handler'))
        ;

        $this->dispatcher($I)->dispatch(new WorkerMessageHandledEvent($envelope, 'async'));

        $I->amOnPage(self::DASHBOARD_URL . '/history/' . $this->lastRecordedId($I));
        $I->seeResponseCodeIs(200);
        $I->see('Société Générale');
        // Accent échappé par json_encode. Barre oblique doublée, sinon php-cs-fixer convertit le littéral en caractère.
        $I->assertFalse(str_contains($I->grabPageSource(), '\u00e9'));
    }

    public function testFailedCommandIsRecordedWithItsReason(FunctionalTester $I): void
    {
        $message = new RunCommandMessage('app:qonto:sync-supplier-invoices');
        $envelope = $this->receivedEnvelope($message);
        $failure = new RunCommandFailedException('Command exited with code "1".', new RunCommandContext($message, 1, 'Synchro interrompue : Qonto indisponible.'));

        $this->dispatcher($I)->dispatch(new WorkerMessageFailedEvent($envelope, 'async', new HandlerFailedException($envelope, ['handler' => $failure])));

        $I->amOnPage(self::DASHBOARD_URL . '/history/' . $this->lastRecordedId($I));
        $I->seeResponseCodeIs(200);
        $I->see('Synchro interrompue : Qonto indisponible.');
    }

    public function testFailureAfterClosedEntityManagerIsStillRecorded(FunctionalTester $I): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->close();

        $envelope = $this->receivedEnvelope(new SyncSupplierInvoicesMessage(1));
        $this->dispatcher($I)->dispatch(new WorkerMessageFailedEvent($envelope, 'async', new RuntimeException('Deadlock')));

        /** @var EntityManagerInterface $resetEntityManager */
        $resetEntityManager = $I->grabService('doctrine')->getManagerForClass(ProcessedMessage::class);
        $I->assertTrue($resetEntityManager->isOpen());
        $I->assertNotEmpty($resetEntityManager->getRepository(ProcessedMessage::class)->findBy([], ['id' => 'DESC'], 1));
    }

    public function testHistoryPurgeIsScheduled(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand('debug:scheduler');

        $I->assertStringContainsString('messenger:monitor:purge', $output);
        $I->assertStringContainsString('app:qonto:sync-supplier-invoices', $output);
    }

    private function receivedEnvelope(object $message): Envelope
    {
        return new Envelope($message, [(new MonitorStamp())->markReceived('async')]);
    }

    private function dispatcher(FunctionalTester $I): EventDispatcherInterface
    {
        return $I->grabService('event_dispatcher');
    }

    private function lastRecordedId(FunctionalTester $I): int
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $processedMessages = $entityManager->getRepository(ProcessedMessage::class)->findBy([], ['id' => 'DESC'], 1);
        $I->assertNotEmpty($processedMessages);

        return (int) $processedMessages[0]->id();
    }
}
