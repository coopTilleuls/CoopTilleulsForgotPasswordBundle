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

namespace CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\Serializer;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    private $decorated;
    private $router;

    public function __construct(NormalizerInterface $decorated, RouterInterface $router)
    {
        $this->decorated = $decorated;
        $this->router = $router;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $routes = $this->router->getRouteCollection();
        $docs = $this->decorated->normalize($object, $format, $context);

        // Add POST /forgot-password/ path
        $docs['tags'][] = ['name' => 'Forgot password'];
        $docs['paths'][$routes->get('coop_tilleuls_forgot_password.reset')->getPath()]['post'] = [
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
        ];
        $docs['components']['schemas']['ForgotPassword:request'] = [
            'type' => 'object',
            'description' => '',
            'required' => ['email'],
            'properties' => [
                'email' => [
                    'type' => 'string',
                ],
                'provider' => [
                    'type' => 'string',
                ],
            ],
        ];

        // Add GET /forgot-password/{tokenValue} path
        $docs['paths'][$routes->get('coop_tilleuls_forgot_password.get_token')->getPath()]['get'] = [
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
        ];
        $docs['components']['schemas']['ForgotPassword:validate'] = [
            'type' => 'object',
            'description' => '',
        ];

        // Add POST /forgot-password/{tokenValue} path
        $docs['paths'][$routes->get('coop_tilleuls_forgot_password.update')->getPath()]['post'] = [
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
        ];
        $docs['components']['schemas']['ForgotPassword:reset'] = [
            'type' => 'object',
            'description' => '',
            'required' => ['password'],
            'properties' => [
                'password' => [
                    'type' => 'string',
                ],
            ],
        ];

        return $docs;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
