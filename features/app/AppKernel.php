<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use ForgotPasswordBundle\ForgotPasswordBundle;
use ForgotPasswordBundle\Tests\TestBundle\TestBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Test purpose micro-kernel.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new StofDoctrineExtensionsBundle(),
            new DoctrineBundle(),
            new SwiftmailerBundle(),
            new TestBundle(),
            new ForgotPasswordBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('@ForgotPasswordBundle/Controller/', '/forgot_password');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('forgot_password', [
            'password_token_class' => 'ForgotPasswordBundle\Tests\TestBundle\Entity\PasswordToken',
            'user_class' => 'ForgotPasswordBundle\Tests\TestBundle\Entity\User',
            'user_field' => 'username',
        ]);

        $c->loadFromExtension('stof_doctrine_extensions', [
            'orm' => [
                'default' => [
                    'timestampable' => true,
                ],
            ],
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

        $c->loadFromExtension('framework', [
            'secret' => 'ForgotPasswordBundle',
            'test' => null,
            'serializer' => ['enabled' => true],
        ]);

        $c->loadFromExtension('security', [
            'encoders' => [UserInterface::class => 'plaintext'],
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'admin' => ['password' => 'admin'],
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
