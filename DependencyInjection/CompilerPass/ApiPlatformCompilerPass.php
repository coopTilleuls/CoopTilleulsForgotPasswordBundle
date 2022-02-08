<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ApiPlatformCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('api_platform.swagger.normalizer.documentation')) {
            $container->removeDefinition('coop_tilleuls_forgot_password.normalizer.documentation');
        }

        if (!$container->hasDefinition('api_platform.openapi.factory')) {
            $container->removeDefinition('coop_tilleuls_forgot_password.openapi.factory');
        }
    }
}
