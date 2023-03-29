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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        if (method_exists(TreeBuilder::class, 'root')) {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('coop_tilleuls_forgot_password');
        } else {
            $treeBuilder = new TreeBuilder('coop_tilleuls_forgot_password');
            $rootNode = $treeBuilder->getRootNode();
        }

        $rootNode
            ->beforeNormalization()
            ->ifTrue(function ($config) {
                return \array_key_exists('password_token_class', $config) || \array_key_exists('user_class', $config);
            })
            ->then(function ($config) {
                if (\array_key_exists('password_token_class', $config)) {
                    if (!isset($config['password_token'])) {
                        $config['password_token'] = [];
                    }
                    $config['password_token']['class'] = $config['password_token_class'];
                }

                if (\array_key_exists('user_class', $config)) {
                    if (!isset($config['user'])) {
                        $config['user'] = [];
                    }
                    $config['user']['class'] = $config['user_class'];
                }
                unset($config['password_token_class'], $config['user_class']);

                return $config;
            })
            ->ifTrue(function ($config) {
                return !\array_key_exists('providers', $config);
            })
            ->then(function ($config) {
                $config['providers']['default']['default'] = true;
                $config['providers']['default']['password_token'] = $config['password_token'];
                $config['providers']['default']['user'] = $config['user'];
                $config['providers']['default']['manager'] = $config['manager'];
                unset($config['user'], $config['password_token'], $config['manager']);

                return $config;
            })
            ->end()
            ->children()
                ->arrayNode('providers')->useAttributeAsKey('name')->prototype('array')
                    ->children()
                        ->scalarNode('manager')->defaultValue('coop_tilleuls_forgot_password.manager.doctrine')->cannotBeEmpty()->info('Persistence manager service to handle the token storage.')->end()
                        ->booleanNode('default')->defaultFalse()->end()
                        ->arrayNode('password_token')
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->isRequired()->info('PasswordToken class.')->end()
                                ->scalarNode('expires_in')->defaultValue('1 day')->cannotBeEmpty()->info('Expiration time using Datetime format. see : https://www.php.net/manual/en/datetime.format.php.')->end()
                                ->scalarNode('user_field')->defaultValue('user')->cannotBeEmpty()->info('User field name on PasswordToken entity.')->end()
                                ->arrayNode('serialization_groups')->info('PasswordToken serialization groups.')->defaultValue([])->useAttributeAsKey('name')->prototype('scalar')
                                ->end()
                                ->end()
                             ->end()
                        ->end()
                        ->arrayNode('user')
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->isRequired()->info('User class.')->end()
                                ->scalarNode('email_field')->defaultValue('email')->cannotBeEmpty()->info('User email field name to retrieve it (email, username...).')->end()
                                ->scalarNode('password_field')->defaultValue('password')->cannotBeEmpty()->info('User password field name.')->end()
                                ->arrayNode('authorized_fields')->defaultValue(['email'])->requiresAtLeastOneElement()->info('User fields names to retrieve it (email, username...).')->prototype('scalar')->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('use_jms_serializer')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
