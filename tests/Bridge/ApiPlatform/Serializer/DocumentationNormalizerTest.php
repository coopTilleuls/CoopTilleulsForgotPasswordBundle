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

namespace CoopTilleuls\ForgotPasswordBundle\Tests\Bridge\ApiPlatform\Serializer;

use CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\Serializer\DocumentationNormalizer;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use CoopTilleuls\ForgotPasswordBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class DocumentationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NormalizerInterface|ObjectProphecy
     */
    private $normalizerMock;

    /**
     * @var RouterInterface|ObjectProphecy
     */
    private $routerMock;

    /**
     * @var RouteCollection|ObjectProphecy
     */
    private $routeCollectionMock;

    /**
     * @var Route|ObjectProphecy
     */
    private $routeMock;

    /**
     * @var ProviderChainInterface|ObjectProphecy
     */
    private $providerChainMock;

    /**
     * @var ProviderInterface|ObjectProphecy
     */
    private $providerMock;

    /**
     * @var DocumentationNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizerMock = $this->prophesize(NormalizerInterface::class);
        $this->routerMock = $this->prophesize(RouterInterface::class);
        $this->routeCollectionMock = $this->prophesize(RouteCollection::class);
        $this->routeMock = $this->prophesize(Route::class);
        $this->providerChainMock = $this->prophesize(ProviderChainInterface::class);
        $this->providerMock = $this->prophesize(ProviderInterface::class);
        $this->normalizer = new DocumentationNormalizer(
            $this->normalizerMock->reveal(),
            $this->routerMock->reveal(),
            $this->providerChainMock->reveal()
        );
    }

    public function testItSupportsDecoratedSupport(): void
    {
        $this->normalizerMock->supportsNormalization('foo', 'bar')->willReturn(true)->shouldBeCalledOnce();
        $this->assertTrue($this->normalizer->supportsNormalization('foo', 'bar'));
    }

    public function testItDecoratesNormalizedData(): void
    {
        $this->routerMock->getRouteCollection()->willReturn($this->routeCollectionMock)->shouldBeCalledOnce();
        $this->routeCollectionMock->get('coop_tilleuls_forgot_password.reset')->willReturn($this->routeMock)->shouldBeCalledOnce();
        $this->routeCollectionMock->get('coop_tilleuls_forgot_password.get_token')->willReturn($this->routeMock)->shouldBeCalledOnce();
        $this->routeCollectionMock->get('coop_tilleuls_forgot_password.update')->willReturn($this->routeMock)->shouldBeCalledOnce();
        $this->routeMock->getPath()->willReturn('/api/forgot-password/', '/api/forgot-password/{tokenValue}', '/api/forgot-password/{tokenValue}')->shouldBeCalledTimes(3);

        $this->providerChainMock->all()->willReturn([
            'user' => $this->providerMock->reveal(),
            'admin' => $this->providerMock->reveal(),
        ])->shouldBeCalledOnce();
        $this->providerMock->getUserPasswordField()->willReturn('password', 'password')->shouldBeCalledTimes(2);
        $this->providerMock->getUserAuthorizedFields()->willReturn(['email'], ['username', 'email'])->shouldBeCalledTimes(2);

        $this->normalizerMock->normalize(new \stdClass(), 'bar', [])->willReturn([
            'tags' => [['name' => 'Login']],
            'paths' => [
                '/login' => [
                    'post' => [
                        'tags' => ['Login'],
                        'operationId' => 'login',
                        'summary' => 'Log in',
                        'responses' => [
                            204 => [
                                'description' => 'Valid credentials',
                            ],
                            400 => [
                                'description' => 'Invalid credentials',
                            ],
                        ],
                        'requestBody' => [
                            'description' => 'Log in',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/User:login',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'User:login' => [
                        'type' => 'object',
                        'description' => 'User login object',
                        'required' => ['username', 'password'],
                        'properties' => [
                            'username' => [
                                'type' => 'string',
                            ],
                            'password' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalledOnce();
        $this->assertEquals([
            'tags' => [['name' => 'Login'], ['name' => 'Forgot password']],
            'paths' => [
                '/login' => [
                    'post' => [
                        'tags' => ['Login'],
                        'operationId' => 'login',
                        'summary' => 'Log in',
                        'responses' => [
                            204 => [
                                'description' => 'Valid credentials',
                            ],
                            400 => [
                                'description' => 'Invalid credentials',
                            ],
                        ],
                        'requestBody' => [
                            'description' => 'Log in',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/User:login',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/forgot-password/' => [
                    'post' => [
                        'tags' => ['Forgot password'],
                        'operationId' => 'postForgotPassword',
                        'summary' => 'Generates a token and send email',
                        'responses' => [
                            204 => [
                                'description' => 'Valid email address, no matter if user exists or not',
                            ],
                            400 => [
                                'description' => 'Missing email parameter or invalid format',
                            ],
                        ],
                        'requestBody' => [
                            'description' => 'Request a new password',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ForgotPassword:request',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/forgot-password/{tokenValue}' => [
                    'get' => [
                        'tags' => ['Forgot password'],
                        'operationId' => 'getForgotPassword',
                        'summary' => 'Validates token',
                        'responses' => [
                            200 => [
                                'description' => 'Authenticated user',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/ForgotPassword:validate',
                                        ],
                                    ],
                                ],
                            ],
                            404 => [
                                'description' => 'Token not found or expired',
                            ],
                        ],
                        'parameters' => [
                            [
                                'name' => 'tokenValue',
                                'in' => 'path',
                                'required' => true,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                            [
                                'name' => 'FP-provider',
                                'in' => 'headers',
                                'required' => false,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Forgot password'],
                        'operationId' => 'postForgotPasswordToken',
                        'summary' => 'Resets user password from token',
                        'responses' => [
                            204 => [
                                'description' => 'Email address format valid, no matter if user exists or not',
                            ],
                            400 => [
                                'description' => 'Missing password parameter',
                            ],
                            404 => [
                                'description' => 'Token not found',
                            ],
                        ],
                        'parameters' => [
                            [
                                'name' => 'tokenValue',
                                'in' => 'path',
                                'required' => true,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        'requestBody' => [
                            'description' => 'Reset password',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ForgotPassword:reset',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'User:login' => [
                        'type' => 'object',
                        'description' => 'User login object',
                        'required' => ['username', 'password'],
                        'properties' => [
                            'username' => [
                                'type' => 'string',
                            ],
                            'password' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'ForgotPassword:request' => [
                        'description' => 'New password request object',
                        'oneOf' => [
                            [
                                'type' => 'object',
                                'required' => ['email'],
                                'properties' => [
                                    'email' => [
                                        'type' => ['string', 'integer'],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'object',
                                'required' => ['username'],
                                'properties' => [
                                    'username' => [
                                        'type' => ['string', 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ForgotPassword:validate' => [
                        'type' => ['object', 'null'],
                        'description' => 'Authenticated user',
                    ],
                    'ForgotPassword:reset' => [
                        'type' => 'object',
                        'description' => 'Reset password object',
                        'required' => ['password'],
                        'properties' => [
                            'password' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->normalizer->normalize(new \stdClass(), 'bar'));
    }
}
