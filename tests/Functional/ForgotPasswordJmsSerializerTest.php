<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Functional;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Same functional tests as ForgotPasswordTest but using the JMS Serializer environment.
 *
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordJmsSerializerTest extends ForgotPasswordTest
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['environment'] ??= 'jmsserializer';
        $options['debug'] ??= false;

        return parent::createKernel($options);
    }
}
