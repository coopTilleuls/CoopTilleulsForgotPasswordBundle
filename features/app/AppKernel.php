<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
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
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new ForgotPasswordBundle\ForgotPasswordBundle(),
            new ForgotPasswordBundle\Tests\TestBundle\TestBundle(),
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
            'user_field' => 'email',
        ]);

        $c->loadFromExtension('stof_doctrine_extensions', [
            'orm' => [
                'default' => [
                    'timestampable' => true,
                ],
            ],
        ]);

        $c->loadFromExtension('swiftmailer', [
            'disable_delivery' => true,
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
            'profiler' => ['collect' => false],
            'templating' => [
                'engines' => ['twig'],
            ],
        ]);

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
