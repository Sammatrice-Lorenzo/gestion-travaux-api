<?php

namespace App\Helper;

final class DateFormatHelper
{
    public const string DEFAULT_FORMAT = 'Y-m-d';

    public const string FRENCH_FORMAT = 'd/m/y';

    public const string YEAR_FORMAT = 'Y';

    public const string MONTH_FORMAT = 'm';

    public const string TIME_FORMAT = 'H:i';

    public const string LAST_DAY_FORMAT = 'Y-m-t';

    public const string DEFAULT_FORMAT_WITH_TIME = self::DEFAULT_FORMAT . ' ' . self::TIME_FORMAT ;
}
