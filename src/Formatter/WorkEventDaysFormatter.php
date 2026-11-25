<?php

declare(strict_types=1);

namespace App\Formatter;

use App\Entity\Client;
use App\Entity\WorkEventDay;
use App\Helper\DateFormatHelper;

final class WorkEventDaysFormatter
{
    /**
     * @param WorkEventDay[] $workEventDays
     *
     * @return array<array<int, string>>
     */
    public static function getWorkDayEventFormattedForFile(
        array $workEventDays,
        ?string $timeZone = null,
    ): array {
        return array_map(static fn (WorkEventDay $workEventDay): array => [
            DateFormatHelper::format($workEventDay->getStartDate(), DateFormatHelper::FRENCH_FORMAT, $timeZone),
            $workEventDay->getTitle(),
            DateFormatHelper::format($workEventDay->getStartDate(), DateFormatHelper::TIME_FORMAT, $timeZone),
            DateFormatHelper::format($workEventDay->getEndDate(), DateFormatHelper::TIME_FORMAT, $timeZone),
            $workEventDay->getClient() instanceof Client ? $workEventDay->getClient()->getName() : '',
        ], $workEventDays);
    }

    /**
     * @param WorkEventDay[] $workEventDays
     *
     * @return array<array<int, string>>
     */
    public static function getWorkDayEventFormattedForSearchExport(
        array $workEventDays,
        ?string $timeZone = null,
    ): array {
        return array_map(static fn (WorkEventDay $workEventDay): array => [
            $workEventDay->getTitle(),
            DateFormatHelper::format($workEventDay->getStartDate(), DateFormatHelper::FRENCH_FORMAT_WITH_TIME, $timeZone),
            DateFormatHelper::format($workEventDay->getEndDate(), DateFormatHelper::FRENCH_FORMAT_WITH_TIME, $timeZone),
            $workEventDay->getClient() instanceof Client ? $workEventDay->getClient()->getName() : '',
        ], $workEventDays);
    }
}
