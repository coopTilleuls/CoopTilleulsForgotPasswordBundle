<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('coop_tilleuls_forgot_password');

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($config) {
                    return array_key_exists('password_token_class', $config) || array_key_exists('user_class', $config);
                })
                ->then(function ($config) {
                    if (array_key_exists('password_token_class', $config)) {
                        if (!isset($config['password_token'])) {
                            $config['password_token'] = [];
                        }
                        $config['password_token']['class'] = $config['password_token_class'];
                    }
                    if (array_key_exists('user_class', $config)) {
                        if (!isset($config['user'])) {
                            $config['user'] = [];
                        }
                        $config['user']['class'] = $config['user_class'];
                    }
                    unset($config['password_token_class'], $config['user_class']);

                    return $config;
                })
            ->end()
            ->children()
                ->scalarNode('manager')->defaultValue('coop_tilleuls_forgot_password.manager.doctrine')->cannotBeEmpty()->info('Manager service.')->end()
                ->arrayNode('password_token')
                    ->children()
                        ->scalarNode('class')->cannotBeEmpty()->isRequired()->info('PasswordToken class.')->end()
                        ->scalarNode('expires_in')->defaultValue('1 day')->cannotBeEmpty()->info('Expiration time.')->end()
                        ->scalarNode('user_field')->defaultValue('user')->cannotBeEmpty()->info('User field name on PasswordToken entity.')->end()
                        ->arrayNode('serialization_groups')
                            ->info('PasswordToken serialization groups.')
                            ->defaultValue([])
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('user')
                    ->children()
                        ->scalarNode('class')->cannotBeEmpty()->isRequired()->info('User class.')->end()
                        ->scalarNode('email_field')->defaultValue('email')->cannotBeEmpty()->info('User email field name to retrieve it (email, username...).')->end()
                        ->arrayNode('authorized_fields')
                            ->defaultValue(['email'])
                            ->requiresAtLeastOneElement()
                            ->info('User fields names to retrieve it (email, username...).')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('password_field')->defaultValue('password')->cannotBeEmpty()->info('User password field name.')->end()
                    ->end()
                ->end()
                ->booleanNode('use_jms_serializer')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
