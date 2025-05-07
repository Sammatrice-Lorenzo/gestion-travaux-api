<?php

namespace App\Helper;

use DateTime;
use DatePeriod;
use DateInterval;

final class DateHelper
{
    /**
     * @var array<string, string>
     */
    public const array FRENCH_MONTHS = [
        '01' => 'Janvier',
        '02' => 'Février',
        '03' => 'Mars',
        '04' => 'Avril',
        '05' => 'Mai',
        '06' => 'Juin',
        '07' => 'Juillet',
        '08' => 'Août',
        '09' => 'Septembre',
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre',
    ];

    public static function getDatePeriodForMonth(DateTime $date): DatePeriod
    {
        $firstDayOfMonth = new DateTime("{$date->format('Y-m')}-01");
        $lastDayOfMonth = new DateTime("{$date->format(DateFormatHelper::LAST_DAY_FORMAT)}");
        $interval = new DateInterval('P1D');

        return new DatePeriod($firstDayOfMonth, $interval, $lastDayOfMonth);
    }
}
