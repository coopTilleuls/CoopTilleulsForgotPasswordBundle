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

use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    private $decorated;
    private $router;
    private $providerChain;

    public function __construct(NormalizerInterface $decorated, RouterInterface $router, ProviderChainInterface $providerChain)
    {
        $this->decorated = $decorated;
        $this->router = $router;
        $this->providerChain = $providerChain;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $routes = $this->router->getRouteCollection();
        $docs = $this->decorated->normalize($object, $format, $context);

        $resetProperties = [];
        $requestProperties = [];
        foreach ($this->providerChain->all() as $provider) {
            $userPasswordField = $provider->getUserPasswordField();
            if (!\array_key_exists($userPasswordField, $resetProperties)) {
                $resetProperties[$userPasswordField] = [
                    'type' => 'object',
                    'required' => [$userPasswordField],
                    'properties' => [
                        $userPasswordField => ['type' => 'string'],
                    ],
                ];
            }

            $userAuthorizedFields = $provider->getUserAuthorizedFields();
            foreach ($userAuthorizedFields as $userAuthorizedField) {
                if (!\array_key_exists($userAuthorizedField, $requestProperties)) {
                    $requestProperties[$userAuthorizedField] = [
                        'type' => 'object',
                        'required' => [$userAuthorizedField],
                        'properties' => [
                            $userAuthorizedField => [
                                'type' => ['string', 'integer'],
                            ],
                        ],
                    ];
                }
            }
        }
        $resetSchema = 1 < \count($resetProperties) ? ['oneOf' => array_values($resetProperties)] : array_values($resetProperties)[0];
        $requestSchema = 1 < \count($requestProperties) ? ['oneOf' => array_values($requestProperties)] : array_values($requestProperties)[0];

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
        $docs['components']['schemas']['ForgotPassword:request'] = array_merge([
            'description' => 'New password request object',
        ], $requestSchema);

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
            'type' => ['object', 'null'],
            'description' => 'Authenticated user',
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
        $docs['components']['schemas']['ForgotPassword:reset'] = array_merge([
            'description' => 'Reset password object',
        ], $resetSchema);

        return $docs;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function getSupportedTypes(?string $format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.4 is dropped
        if (!method_exists($this->decorated, 'getSupportedTypes')) {
            return [
                '*' => $this->decorated instanceof CacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod(),
            ];
        }

        return $this->decorated->getSupportedTypes($format);
    }
}
