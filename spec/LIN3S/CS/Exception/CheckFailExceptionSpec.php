<?php

/*
 * This file is part of the CS library.
 *
 * Copyright (c) 2015-present LIN3S <info@lin3s.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\LIN3S\CS\Exception;

use LIN3S\CS\Exception\CheckFailException;
use PhpSpec\ObjectBehavior;

/**
 * Spec file of CheckFail exception class.
 *
 * @author Beñat Espiña <benatespina@gmail.com>
 */
class CheckFailExceptionSpec extends ObjectBehavior
{
    function it_can_be_thrown()
    {
        $this->beConstructedWith('Dummy-Check-Name', 'Dummy message');

        $this->shouldHaveType(CheckFailException::class);
        $this->shouldHaveType(\Exception::class);

        $this->getMessage()->shouldReturn('Check fails during the Dummy-Check-Name. Dummy message');
    }
}
