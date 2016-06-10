<?php

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
            ->children()
                ->scalarNode('password_token_class')->cannotBeEmpty()->isRequired()->info('PasswordToken class.')->end()
                ->scalarNode('user_class')->cannotBeEmpty()->isRequired()->info('User class.')->end()
                ->scalarNode('email_field')->defaultValue('email')->cannotBeEmpty()->info('User email field name to retrieve user (email, username...).')->end()
                ->scalarNode('password_field')->defaultValue('password')->cannotBeEmpty()->info('User password field name.')->end()
                ->scalarNode('expires_in')->defaultValue('1 day')->cannotBeEmpty()->info('Expiration time.')->end()
                ->scalarNode('manager')->defaultValue('coop_tilleuls_forgot_password.manager.doctrine')->cannotBeEmpty()->info('Manager service.')->end()
                ->arrayNode('groups')
                    ->info('PasswordToken serialization groups.')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
