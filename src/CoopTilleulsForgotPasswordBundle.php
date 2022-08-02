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

namespace CoopTilleuls\ForgotPasswordBundle;

use CoopTilleuls\ForgotPasswordBundle\DependencyInjection\CompilerPass\ApiPlatformCompilerPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class CoopTilleulsForgotPasswordBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ApiPlatformCompilerPass());
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver([
            realpath(__DIR__.'/../config/doctrine') => 'CoopTilleuls\ForgotPasswordBundle\Entity',
        ]));
    }
}
