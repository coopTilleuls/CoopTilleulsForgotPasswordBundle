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

namespace CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface as LegacyOpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi as LegacyOpenApi;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactory;
use Symfony\Component\Routing\RouterInterface;

if (interface_exists(OpenApiFactoryInterface::class)) {
    final class OpenApiFactory extends AbstractOpenApiFactory implements OpenApiFactoryInterface
    {
        public function __construct(OpenApiFactoryInterface $decorated, RouterInterface $router, ProviderFactory $providerFactory)
        {
            parent::__construct($decorated, $router, $providerFactory);
        }

        public function __invoke(array $context = []): OpenApi
        {
            return parent::__invoke($context);
        }
    }
} else {
    final class OpenApiFactory extends AbstractOpenApiFactory implements LegacyOpenApiFactoryInterface
    {
        public function __construct(LegacyOpenApiFactoryInterface $decorated, RouterInterface $router, ProviderFactory $providerFactory)
        {
            parent::__construct($decorated, $router, $providerFactory);
        }

        public function __invoke(array $context = []): LegacyOpenApi
        {
            return parent::__invoke($context);
        }
    }
}
