<?php

namespace App\Formatter;

use App\Entity\WorkEventDay;
use App\Helper\DateFormatHelper;

final class WorkEventDaysFormatter
{
    /**
     * @param WorkEventDay[] $workEventDays
     * @return array<int, array<string, string>>
     */
    public static function getWorkDayEventFormattedForFile(array $workEventDays): array
    {
        $timeFormat = DateFormatHelper::TIME_FORMAT;

        return array_map(static fn (WorkEventDay $workEventDay) => [
            $workEventDay->getStartDate()->format(DateFormatHelper::FRENCH_FORMAT),
            $workEventDay->getTitle(),
            $workEventDay->getStartDate()->format($timeFormat),
            $workEventDay->getEndDate()->format($timeFormat),
            $workEventDay->getClient() ? $workEventDay->getClient()->getName() : '',
        ], $workEventDays);
    }
}
