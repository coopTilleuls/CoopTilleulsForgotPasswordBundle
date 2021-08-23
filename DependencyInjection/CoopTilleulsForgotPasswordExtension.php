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

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use CoopTilleuls\ForgotPasswordBundle\Normalizer\JMSNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\SymfonyNormalizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class CoopTilleulsForgotPasswordExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Build parameters
        $container->setParameter('coop_tilleuls_forgot_password.password_token_class', $config['password_token']['class']);
        $container->setParameter('coop_tilleuls_forgot_password.password_token_expires_in', $config['password_token']['expires_in']);
        $container->setParameter('coop_tilleuls_forgot_password.password_token_user_field', $config['password_token']['user_field']);
        $container->setParameter('coop_tilleuls_forgot_password.password_token_serialization_groups', $config['password_token']['serialization_groups']);

        $container->setParameter('coop_tilleuls_forgot_password.user_class', $config['user']['class']);
        $config['user']['authorized_fields'] = array_unique(array_merge($config['user']['authorized_fields'], [$config['user']['email_field']]));
        unset($config['user']['email_field']);
        $container->setParameter('coop_tilleuls_forgot_password.user_authorized_fields', $config['user']['authorized_fields']);
        $container->setParameter('coop_tilleuls_forgot_password.user_password_field', $config['user']['password_field']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // Load API-Platform bridge
        if (isset($container->getParameter('kernel.bundles')['ApiPlatformBundle'])) {
            $loader->load('api_platform.xml');
        }

        $container->setAlias('coop_tilleuls_forgot_password.manager', $config['manager']);

        // Build normalizer
        $class = true === $config['use_jms_serializer'] ? JMSNormalizer::class : SymfonyNormalizer::class;
        $serializerId = true === $config['use_jms_serializer'] ? 'jms_serializer.serializer' : 'serializer';
        $container->setDefinition('coop_tilleuls_forgot_password.normalizer', new Definition($class, [new Reference($serializerId)]))->setPublic(false);

        if (!$container->hasDefinition('api_platform.swagger.naormalizer.documentation')) {
            $container->removeDefinition('coop_tilleuls_forgot_password.normalizer.documentation');
        }

        if (!$container->hasDefinition('api_platform.openapi.factory')) {
            $container->removeDefinition('coop_tilleuls_forgot_password.openapi.factory');
        }
    }
}
