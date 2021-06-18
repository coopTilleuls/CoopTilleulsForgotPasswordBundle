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
use ApiPlatform\Core\OpenApi\Model\Components;
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
        $paths = $openApi->withTags(['name' => 'Forgot password'])->getPaths();

        // Add POST /forgot-password/ path
        /** @var PathItem $path */
        $path = $paths->getPath('/forgot-password/');
        $paths->addPath('/forgot-password/', $path->withPost(new Operation(
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
            new RequestBody('Request a new password', new \ArrayObject([
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/ForgotPassword:request',
                    ],
                ],
            ]))
        )));
        $openApi->withComponents(new Components(new \ArrayObject([
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
        ])));

        // Add GET /forgot-password/{token} path
        /** @var PathItem $path */
        $path = $paths->getPath('/forgot-password/{token}');
        $paths->addPath('/forgot-password/', $path->withGet(new Operation(
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
        )));
        $openApi->withComponents(new Components(new \ArrayObject([
            'ForgotPassword:validate' => [
                'type' => 'object',
                'description' => '',
            ],
        ])));

        // Add POST /forgot-password/{token} path
        /** @var PathItem $path */
        $path = $paths->getPath('/forgot-password/{token}');
        $paths->addPath('/forgot-password/', $path->withPost(new Operation(
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
            new RequestBody('Reset password', new \ArrayObject([
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/ForgotPassword:reset',
                    ],
                ],
            ]))
        )));
        $openApi->withComponents(new Components(new \ArrayObject([
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
        ])));

        return $openApi;
    }
}
