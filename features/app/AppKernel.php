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

use App\Entity\Admin;
use App\Entity\PasswordAdminToken;
use App\Entity\PasswordToken;
use App\Entity\User;
use App\EventListener\ForgotPasswordEventListener;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Test purpose micro-kernel.
 *
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        return __DIR__.'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return __DIR__.'/var/logs/'.$this->getEnvironment();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function registerBundles(): array
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle(),
            new CoopTilleuls\ForgotPasswordBundle\CoopTilleulsForgotPasswordBundle(),
        ];
        if ('jmsserializer' === $this->getEnvironment()) {
            $bundles[] = new JMS\SerializerBundle\JMSSerializerBundle();
        }
        if (class_exists(ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class)) {
            $bundles[] = new ApiPlatform\Symfony\Bundle\ApiPlatformBundle();
        } else {
            // BC api-platform/core:^2.7
            $bundles[] = new ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle();
        }

        return $bundles;
    }

    /**
     * @param RoutingConfigurator|RouteCollectionBuilder $routes
     */
    protected function configureRoutes($routes): void
    {
        if ($routes instanceof RoutingConfigurator) {
            $routes->import('.', 'coop_tilleuls_forgot_password')->prefix('/api/forgot-password');
            $routes->import('.', 'api_platform')->prefix('/api');

            return;
        }

        // BC
        $routes->import('@CoopTilleulsForgotPasswordBundle/Resources/config/routing.xml', '/api/forgot-password');
        $routes->import('.', '/api', 'api_platform');
    }

    /**
     * @param ContainerConfigurator|ContainerBuilder $container
     */
    protected function configureContainer($container, LoaderInterface $loader): void
    {
        if ($container instanceof ContainerConfigurator) {
            $container->services()->set(ForgotPasswordEventListener::class)->args([
                new Reference('mailer'),
                new Reference('twig'),
                new Reference('doctrine'),
            ])->tag('kernel.event_subscriber');
            $container->services()->set(\FeatureContext::class, \FeatureContext::class)->args([
                new Reference('test.client'),
                new Reference('doctrine'),
                new Reference('coop_tilleuls_forgot_password.manager.password_token'),
                new Reference('coop_tilleuls_forgot_password.provider_chain'),
                new Reference('kernel'),
            ])->public();
        } else {
            $container->setDefinition(ForgotPasswordEventListener::class, (new Definition(ForgotPasswordEventListener::class, [
                new Reference('mailer'),
                new Reference('twig'),
                new Reference('doctrine'),
            ]))->addTag('kernel.event_subscriber'));
            $container->setDefinition(\FeatureContext::class, (new Definition(\FeatureContext::class, [
                new Reference('test.client'),
                new Reference('doctrine'),
                new Reference('coop_tilleuls_forgot_password.manager.password_token'),
                new Reference('kernel'),
            ]))->setPublic(true));
        }

        $method = $container instanceof ContainerConfigurator ? 'extension' : 'loadFromExtension';

        $container->{$method}('coop_tilleuls_forgot_password', [
            'providers' => [
                'user' => [
                    'default' => true,
                    'password_token' => [
                        'class' => PasswordToken::class,
                        'expires_in' => '+1 day',
                        'user_field' => 'user',
                        'serialization_groups' => [],
                    ],
                    'user' => [
                        'class' => User::class,
                        'email_field' => 'email',
                        'password_field' => 'password',
                        'authorized_fields' => ['email'],
                    ],
                ],
                'admin' => [
                    'password_token' => [
                        'class' => PasswordAdminToken::class,
                        'expires_in' => '+4 hours',
                        'user_field' => 'admin',
                        'serialization_groups' => [],
                    ],
                    'user' => [
                        'class' => Admin::class,
                        'email_field' => 'username',
                        'password_field' => 'adminPassword',
                        'authorized_fields' => ['username', 'email'],
                    ],
                ],
            ],
            'use_jms_serializer' => 'jmsserializer' === $this->getEnvironment(),
        ]);

        $container->{$method}('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'path' => '%kernel.cache_dir%/db.sqlite',
                'charset' => 'UTF8',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                'auto_mapping' => true,
                'mappings' => [
                    'App' => [
                        'is_bundle' => false,
                        'type' => 'annotation',
                        'dir' => $this->getProjectDir().'/src/Entity',
                        'prefix' => 'App\Entity',
                        'alias' => 'App',
                    ],
                ],
            ],
        ]);

        $container->{$method}('framework', array_merge([
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

        $firewallExtra = ['lazy' => true];
        $passwordHashers = [
            'password_hashers' => [
                UserInterface::class => [
                    'algorithm' => 'plaintext',
                ],
            ],
        ];
        $anonymousRole = 'PUBLIC_ACCESS';

        if (6 > Kernel::MAJOR_VERSION) {
            $firewallExtra = ['anonymous' => true];
            $passwordHashers = ['encoders' => [UserInterface::class => 'plaintext']];
            $anonymousRole = 'IS_AUTHENTICATED_ANONYMOUSLY';
        }

        $container->{$method}('security', $passwordHashers + [
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'john.doe@example.com' => ['password' => 'P4$$w0rd'],
                        ],
                    ],
                ],
                'in_memory_admin' => [
                    'memory' => [
                        'users' => [
                            'admin@example.com' => ['password' => 'P4$$w0rd'],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'docs' => [
                    'pattern' => '^/api/((index|docs|contexts/[A-z]+)(\.[A-z]+)?)?$',
                    'security' => false,
                ],
                'main' => [
                    'pattern' => '^/',
                    'provider' => 'in_memory',
                    'stateless' => true,
                    'http_basic' => null,
                ] + $firewallExtra,
                'admin' => [
                    'pattern' => '^/admin',
                    'provider' => 'in_memory_admin',
                    'stateless' => true,
                    'http_basic' => null,
                ] + $firewallExtra,
            ],
            'access_control' => [
                ['path' => '^/api/forgot-password', 'roles' => $anonymousRole],
                ['path' => '^/', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ],
        ]);
    }
}
