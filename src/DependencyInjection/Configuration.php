<?php

namespace ForgotPasswordBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('forgot_password');

        $rootNode
            ->children()
                ->scalarNode('password_token_class')->cannotBeEmpty()->isRequired()->info('PasswordToken class.')->end()
                ->scalarNode('user_class')->cannotBeEmpty()->isRequired()->info('User class.')->end()
                ->scalarNode('user_field')->defaultValue('email')->cannotBeEmpty()->info('User field name to retrieve user (email, username...).')->end()
            ->end();

        return $treeBuilder;
    }
}
