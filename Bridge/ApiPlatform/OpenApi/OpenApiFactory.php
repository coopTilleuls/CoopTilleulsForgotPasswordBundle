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

namespace CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\Model\RequestBody;
use ApiPlatform\Core\OpenApi\OpenApi;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    private $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['ForgotPassword:reset'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['password'],
            'properties' => [
                'password' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $schemas['ForgotPassword:validate'] = new \ArrayObject([
            'type' => 'object',
        ]);

        $schemas['ForgotPassword:request'] = new \ArrayObject([
            'type' => 'object',
            'required' => ['email'],
            'properties' => [
                'email' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $pathItem = new PathItem(
            'ForgotPassword',
            null,
            null,
            null,
            null,
            new Operation(
                'postForgotPassword',
                ['Forgot password'],
                [
                    204 => [
                        'description' => 'Valid email address, no matter if user exists or not',
                    ],
                    400 => [
                        'description' => 'Missing email parameter or invalid format',
                    ],
                ],
                'Generates a token and send email',
                '',
                null,
                [],
                new RequestBody(
                    'Request a new password',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:request',
                            ],
                        ],
                    ])
                )
            )
        );
        $openApi->getPaths()->addPath('/forgot_password/', $pathItem);

        $pathItem = new PathItem(
            'ForgotPassword',
            null,
            '',
            new Operation(
                'getForgotPassword',
                ['Forgot password'],
                [
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
                'Validates token',
                '',
                null,
                [
                    [
                        'name' => 'token',
                        'in' => 'path',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ]
            ),
            null,
            new Operation(
                'postForgotPasswordToken',
                ['Forgot password'],
                [
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
                'Resets user password from token',
                '',
                null,
                [
                    [
                        'name' => 'token',
                        'in' => 'path',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                new RequestBody(
                    'Reset password',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:reset',
                            ],
                        ],
                    ])
                )
            )
        );
        $openApi->getPaths()->addPath('/forgot_password/{token}', $pathItem);

        return $openApi;
    }
}
