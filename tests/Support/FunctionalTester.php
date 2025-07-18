<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;
use App\Tests\Support\Trait\GrabEntityTrait;

/**
 * Inherited Methods.
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
final class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;
    use GrabEntityTrait;
}
