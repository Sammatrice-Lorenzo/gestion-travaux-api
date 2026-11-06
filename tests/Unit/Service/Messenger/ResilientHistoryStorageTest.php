<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Messenger;

use App\Service\Messenger\ResilientHistoryStorage;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Zenstruck\Messenger\Monitor\History\Model\Results;
use Zenstruck\Messenger\Monitor\History\Storage;

final class ResilientHistoryStorageTest extends Unit
{
    private Storage&MockObject $inner;

    private ManagerRegistry&MockObject $doctrine;

    private EntityManagerInterface&MockObject $manager;

    protected function _before(): void
    {
        $this->inner = $this->createMock(Storage::class);
        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->method('getManagerForClass')->willReturn($this->manager);
    }

    public function testOpenManagerIsNotReset(): void
    {
        $this->manager->method('isOpen')->willReturn(true);
        $this->doctrine->expects($this->never())->method('resetManager');
        $this->inner->expects($this->once())->method('save');

        $this->storage()->save(new Envelope(new stdClass()), new Results([]));
    }

    public function testClosedManagerIsResetBeforeSaving(): void
    {
        $this->manager->method('isOpen')->willReturn(false);
        $this->doctrine->expects($this->once())->method('resetManager');
        $this->inner->expects($this->once())->method('save');

        $this->storage()->save(new Envelope(new stdClass()), new Results([]));
    }

    public function testSaveFailureIsLoggedWithoutBreakingTheWorker(): void
    {
        $this->manager->method('isOpen')->willReturn(true);
        $this->inner->method('save')->willThrowException(new RuntimeException('Base indisponible'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Historique Messenger non enregistré.');

        (new ResilientHistoryStorage($this->inner, $this->doctrine, $logger))->save(new Envelope(new stdClass()), new Results([]));
    }

    private function storage(): ResilientHistoryStorage
    {
        return new ResilientHistoryStorage($this->inner, $this->doctrine, new NullLogger());
    }
}
