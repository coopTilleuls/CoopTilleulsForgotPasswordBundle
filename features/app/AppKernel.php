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

use CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\Entity\PasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Test purpose micro-kernel.
 *
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function getCacheDir()
    {
        return __DIR__.'/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return __DIR__.'/logs/'.$this->getEnvironment();
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle(),
            new CoopTilleuls\ForgotPasswordBundle\CoopTilleulsForgotPasswordBundle(),
            new CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\CoopTilleulsTestBundle(),
        ];
        if ('jmsserializer' === $this->getEnvironment()) {
            $bundles[] = new JMS\SerializerBundle\JMSSerializerBundle();
        }

        return $bundles;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import('@CoopTilleulsForgotPasswordBundle/Resources/config/routing.xml', '/forgot_password');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('coop_tilleuls_forgot_password', [
            'password_token_class' => PasswordToken::class,
            'user_class' => User::class,
            'user' => [
                'authorized_fields' => ['email', 'username'],
            ],
            'use_jms_serializer' => 'jmsserializer' === $this->getEnvironment(),
        ]);

        $c->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'path' => '%kernel.cache_dir%/db.sqlite',
                'charset' => 'UTF8',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                'auto_mapping' => true,
            ],
        ]);

        $c->loadFromExtension('framework', array_merge([
            'secret' => 'CoopTilleulsForgotPasswordBundle',
            'mailer' => [
                'dsn' => 'null://null',
            ],
            'test' => null,
            'assets' => null,
            'profiler' => ['collect' => false],
        ], 'jmsserializer' !== $this->getEnvironment() ? ['serializer' => null] : [], class_exists(KernelBrowser::class) ? [] : [
            'templating' => [
                'engines' => ['twig'],
            ],
        ]));

        $c->loadFromExtension('security', [
            'encoders' => [UserInterface::class => 'plaintext'],
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'john.doe@example.com' => ['password' => 'P4$$w0rd'],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'stateless' => true,
                    'anonymous' => true,
                    'http_basic' => null,
                ],
            ],
            'access_control' => [
                ['path' => '^/forgot_password', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ],
        ]);
    }
}
