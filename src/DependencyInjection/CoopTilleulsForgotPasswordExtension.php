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

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use CoopTilleuls\ForgotPasswordBundle\Normalizer\JMSNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Normalizer\SymfonyNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Provider\Provider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
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

        if (!$defaultProvider = $this->getDefaultProvider($config)) {
            throw new InvalidConfigurationException('Multiple "ForgotPassword" providers have been defined but none of them is set as default. Did you forget to set "default" option?');
        }

        // Build parameters
        $container->setParameter('coop_tilleuls_forgot_password.password_token_class', $defaultProvider['password_token']['class']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.password_token_class');
        $container->setParameter('coop_tilleuls_forgot_password.password_token_expires_in', $defaultProvider['password_token']['expires_in']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.password_token_expires_in');
        $container->setParameter('coop_tilleuls_forgot_password.password_token_user_field', $defaultProvider['password_token']['user_field']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.password_token_user_field');
        $container->setParameter('coop_tilleuls_forgot_password.password_token_serialization_groups', $defaultProvider['password_token']['serialization_groups']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.password_token_serialization_groups');
        $container->setParameter('coop_tilleuls_forgot_password.user_class', $defaultProvider['user']['class']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.user_class');
        $authorizedFields = array_unique(array_merge($defaultProvider['user']['authorized_fields'], [$defaultProvider['user']['email_field']]));
        $container->setParameter('coop_tilleuls_forgot_password.user_authorized_fields', $authorizedFields);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.user_authorized_fields');
        $container->setParameter('coop_tilleuls_forgot_password.user_password_field', $defaultProvider['user']['password_field']);
        trigger_deprecation('tilleuls/forgot-password-bundle', '1.5', 'Container parameter "%s" is deprecated since 1.5 and will be removed without replacement in 2.0.', 'coop_tilleuls_forgot_password.user_password_field');

        $this->buildProvider($config, $container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');

        // Load API-Platform bridge
        if (isset($container->getParameter('kernel.bundles')['ApiPlatformBundle'])) {
            $loader->load('api_platform.xml');
        }

        $container
            ->setAlias('coop_tilleuls_forgot_password.manager', $defaultProvider['manager'])
            ->setDeprecated('tilleuls/forgot-password-bundle', '1.5', 'Alias "%alias_id%" is deprecated and will be removed without replacement in 2.0.');

        // Build normalizer
        $class = true === $config['use_jms_serializer'] ? JMSNormalizer::class : SymfonyNormalizer::class;
        $serializerId = true === $config['use_jms_serializer'] ? 'jms_serializer.serializer' : 'serializer';
        $container->setDefinition('coop_tilleuls_forgot_password.normalizer', new Definition($class, [new Reference($serializerId)]))->setPublic(false);
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
