<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\OpenApi\OpenApiFactory;
use CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\Serializer\DocumentationNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Controller\ForgotPasswordController;
use CoopTilleuls\ForgotPasswordBundle\Controller\GetToken;
use CoopTilleuls\ForgotPasswordBundle\Controller\ResetPassword as ResetPasswordController;
use CoopTilleuls\ForgotPasswordBundle\Controller\UpdatePassword;
use CoopTilleuls\ForgotPasswordBundle\EventListener\ExceptionEventListener;
use CoopTilleuls\ForgotPasswordBundle\EventListener\RequestEventListener;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\DoctrineManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\JMSNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\SymfonyNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChain;
use CoopTilleuls\ForgotPasswordBundle\Routing\RouteLoader;
use CoopTilleuls\ForgotPasswordBundle\TokenGenerator\Bridge\Bin2HexTokenGenerator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
trait BCExtensionTrait
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$defaultProvider = $this->getDefaultProvider($config)) {
            throw new InvalidConfigurationException('Multiple "ForgotPassword" providers have been defined but none of them is set as default. Did you forget to set "default" option?');
        }

        $this->buildProvider($config, $container);
        $this->registerServices($container);

        // Load API-Platform bridge
        if (isset($container->getParameter('kernel.bundles')['ApiPlatformBundle'])) {
            $this->registerApiPlatformServices($container);
        }

        $alias = $container->setAlias('coop_tilleuls_forgot_password.manager', $defaultProvider['manager']);
        if (method_exists(Definition::class, 'getDeprecation')) {
            $alias->setDeprecated('tilleuls/forgot-password-bundle', '1.5', 'Alias "%alias_id%" is deprecated and will be removed without replacement in 2.0.');
        } else {
            $alias->setDeprecated(true, 'Alias "%alias_id%" is deprecated and will be removed without replacement in 2.0.');
        }

        // Build normalizer
        $class = true === $config['use_jms_serializer'] ? JMSNormalizer::class : SymfonyNormalizer::class;
        $serializerId = true === $config['use_jms_serializer'] ? 'jms_serializer.serializer' : 'serializer';
        $container->setDefinition('coop_tilleuls_forgot_password.normalizer', new Definition($class, [new Reference($serializerId)]))->setPublic(false);

        $container
            ->getDefinition('coop_tilleuls_forgot_password.manager.password_token')
            ->replaceArgument(0, new Reference($config['token_generator']));
    }

    private function registerServices(ContainerBuilder $container): void
    {
        $container->setDefinition('coop_tilleuls_forgot_password.controller.forgot_password',
            (new Definition(ForgotPasswordController::class, [
                new Reference('coop_tilleuls_forgot_password.controller.get_token'),
                new Reference('coop_tilleuls_forgot_password.controller.update_password'),
                new Reference('coop_tilleuls_forgot_password.controller.reset_password'),
            ]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.controller.reset_password',
            (new Definition(ResetPasswordController::class, [
                new Reference('coop_tilleuls_forgot_password.manager.forgot_password'),
            ]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.controller.get_token',
            (new Definition(GetToken::class, [
                new Reference('coop_tilleuls_forgot_password.normalizer'),
            ]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.controller.update_password',
            (new Definition(UpdatePassword::class, [
                new Reference('coop_tilleuls_forgot_password.manager.forgot_password'),
            ]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.manager.forgot_password',
            (new Definition(ForgotPasswordManager::class, [
                new Reference('coop_tilleuls_forgot_password.manager.password_token'),
                new Reference('event_dispatcher'),
            ]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.manager.password_token',
            (new Definition(PasswordTokenManager::class, [null]))->setPublic(true)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.manager.doctrine',
            (new Definition(DoctrineManager::class, [
                new Reference('doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ]))->setPublic(false)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.token_generator.bin2hex',
            (new Definition(Bin2HexTokenGenerator::class))->setPublic(false)
        );

        $container->setDefinition('coop_tilleuls_forgot_password.event_listener.request',
            (new Definition(RequestEventListener::class, [
                new Reference('coop_tilleuls_forgot_password.manager.password_token'),
                new Reference('coop_tilleuls_forgot_password.provider_chain'),
            ]))
                ->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'decodeRequest'])
                ->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'getTokenFromRequest'])
        );

        $container->setDefinition('coop_tilleuls_forgot_password.event_listener.exception',
            (new Definition(ExceptionEventListener::class))
                ->addTag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'onKernelException'])
        );

        $container->setDefinition('coop_tilleuls_forgot_password.route_loader',
            (new Definition(RouteLoader::class))->setPublic(false)->addTag('routing.loader')
        );

        $container->setDefinition('coop_tilleuls_forgot_password.provider_chain',
            (new Definition(ProviderChain::class, [
                new TaggedIteratorArgument('coop_tilleuls_forgot_password.provider', 'key'),
            ]))->setPublic(false)
        );
    }

    private function registerApiPlatformServices(ContainerBuilder $container): void
    {
        $container->setDefinition('coop_tilleuls_forgot_password.normalizer.documentation',
            (new Definition(DocumentationNormalizer::class, [
                new Reference('coop_tilleuls_forgot_password.normalizer.documentation.inner'),
                new Reference('router'),
                new Reference('coop_tilleuls_forgot_password.provider_chain'),
            ]))
                ->setPublic(true)
                ->setDecoratedService('api_platform.swagger.normalizer.documentation')
        );

        $container->setDefinition('coop_tilleuls_forgot_password.openapi.factory',
            (new Definition(OpenApiFactory::class, [
                new Reference('coop_tilleuls_forgot_password.openapi.factory.inner'),
                new Reference('router'),
                new Reference('coop_tilleuls_forgot_password.provider_chain'),
            ]))
                ->setPublic(false)
                ->setDecoratedService('api_platform.openapi.factory')
        );
    }

    private function buildProvider(array $config, ContainerBuilder $container): void
    {
        foreach ($config['providers'] as $key => $value) {
            $container->setDefinition($key, new Definition(Provider::class,
                [
                    new Reference($value['manager']),
                    $key,
                    $value['password_token']['class'],
                    $value['password_token']['expires_in'],
                    $value['password_token']['user_field'],
                    $value['user']['class'],
                    $value['password_token']['serialization_groups'],
                    $value['user']['email_field'],
                    $value['user']['password_field'],
                    array_unique(array_merge($value['user']['authorized_fields'], [$value['user']['email_field']])),
                    $value['default'],
                ]))->setPublic(false)
                ->addTag('coop_tilleuls_forgot_password.provider');
        }
    }

    private function getDefaultProvider(array $config): ?array
    {
        foreach ($config['providers'] as $value) {
            if (true === $value['default']) {
                return $value;
            }
        }

        return null;
    }
}
