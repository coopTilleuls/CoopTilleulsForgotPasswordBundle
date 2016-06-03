<?php

namespace CoopTilleuls\ForgotPasswordBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CoopTilleulsForgotPasswordExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('coop_tilleuls_forgot_password.password_token_class', $config['password_token_class']);
        $container->setParameter('coop_tilleuls_forgot_password.user_class', $config['user_class']);
        $container->setParameter('coop_tilleuls_forgot_password.email_field', $config['email_field']);
        $container->setParameter('coop_tilleuls_forgot_password.password_field', $config['password_field']);
        $container->setParameter('coop_tilleuls_forgot_password.expires_in', $config['expires_in']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
