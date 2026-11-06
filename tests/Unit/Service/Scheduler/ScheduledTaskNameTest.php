<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Scheduler;

use App\Service\Scheduler\ScheduledTaskName;
use Codeception\Test\Unit;
use stdClass;
use Symfony\Component\Console\Messenger\RunCommandMessage;

final class ScheduledTaskNameTest extends Unit
{
    public function testCommandNameWithoutOptions(): void
    {
        $this->assertSame('app:qonto:sync-supplier-invoices', ScheduledTaskName::of(new RunCommandMessage('  app:qonto:sync-supplier-invoices --from=2026-01-01')));
    }

    public function testOtherMessageUsesItsClass(): void
    {
        $this->assertSame(stdClass::class, ScheduledTaskName::of(new stdClass()));
    }
}
