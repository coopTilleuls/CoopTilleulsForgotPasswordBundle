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

namespace Tests\ForgotPasswordBundle\Bridge\ApiPlatform\Serializer;

use CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\Serializer\DocumentationNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Tests\ForgotPasswordBundle\ProphecyTrait;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class DocumentationNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NormalizerInterface|ObjectProphecy
     */
    private $normalizerMock;

    /**
     * @var DocumentationNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizerMock = $this->prophesize(NormalizerInterface::class);
        $this->normalizer = new DocumentationNormalizer($this->normalizerMock->reveal());
    }

    public function testItSupportsDecoratedSupport(): void
    {
        $this->normalizerMock->supportsNormalization('foo', 'bar')->willReturn(true)->shouldBeCalledTimes(1);
        $this->assertTrue($this->normalizer->supportsNormalization('foo', 'bar'));
    }

    public function testItDecoratesNormalizedData(): void
    {
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
                        'description' => '',
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
        ])->shouldBeCalledTimes(1);
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
                '/forgot-password/' => [
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
                '/forgot-password/{token}' => [
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
                                'name' => 'token',
                                'in' => 'path',
                                'required' => true,
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
                                'name' => 'token',
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
                        'description' => '',
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
                        'type' => 'object',
                        'description' => '',
                        'required' => ['email'],
                        'properties' => [
                            'email' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'ForgotPassword:validate' => [
                        'type' => 'object',
                        'description' => '',
                    ],
                    'ForgotPassword:reset' => [
                        'type' => 'object',
                        'description' => '',
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
