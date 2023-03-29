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

namespace CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface as LegacyOpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation as LegacyOperation;
use ApiPlatform\Core\OpenApi\Model\PathItem as LegacyPathItem;
use ApiPlatform\Core\OpenApi\Model\RequestBody as LegacyRequestBody;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
abstract class AbstractOpenApiFactory
{
    protected $decorated;
    protected $router;
    protected $providerChain;

    /**
     * @param LegacyOpenApiFactoryInterface|OpenApiFactoryInterface $decorated
     */
    public function __construct($decorated, RouterInterface $router, ProviderChainInterface $providerChain)
    {
        $this->providerChain = $providerChain;
        $this->decorated = $decorated;
        $this->router = $router;
    }

    public function __invoke(array $context = [])
    {
        $routes = $this->router->getRouteCollection();
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();
        $paths = $openApi->getPaths();

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

        $schemas['ForgotPassword:reset'] = new \ArrayObject($resetSchema);

        $schemas['ForgotPassword:request'] = new \ArrayObject($requestSchema);

        $schemas['ForgotPassword:validate'] = new \ArrayObject([
            'type' => ['object', 'null'],
        ]);

        $resetForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.reset')->getPath();
        $paths->addPath($resetForgotPasswordPath, ($paths->getPath($resetForgotPasswordPath) ?: (class_exists(PathItem::class) ? new PathItem() : new LegacyPathItem()))
            ->withRef('ForgotPassword')
            ->withPost((class_exists(Operation::class) ? new Operation() : new LegacyOperation())
                ->withOperationId('postForgotPassword')
                ->withTags(['Forgot password'])
                ->withResponses([
                    204 => [
                        'description' => 'Valid email address, no matter if user exists or not',
                    ],
                    400 => [
                        'description' => 'Missing email parameter or invalid format',
                    ],
                ])
                ->withSummary('Generates a token and send email')
                ->withParameters([
                    [
                        'name' => 'FP-provider',
                        'in' => 'header',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ])
                ->withRequestBody((class_exists(RequestBody::class) ? new RequestBody() : new LegacyRequestBody())
                    ->withDescription('Request a new password')
                    ->withRequired(true)
                    ->withContent(new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:request',
                            ],
                        ],
                    ])
                    )
                )
            )
        );

        $getForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.get_token')->getPath();
        $paths->addPath($getForgotPasswordPath, ($paths->getPath($getForgotPasswordPath) ?: (class_exists(PathItem::class) ? new PathItem() : new LegacyPathItem()))
            ->withRef('ForgotPassword')
            ->withGet((class_exists(Operation::class) ? new Operation() : new LegacyOperation())
                ->withOperationId('getForgotPassword')
                ->withTags(['Forgot password'])
                ->withResponses([
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
                ])
                ->withSummary('Validates token')
                ->withParameters([
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
                        'in' => 'header',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                )
            )
        );

        $updateForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.update')->getPath();
        $paths->addPath($updateForgotPasswordPath, ($paths->getPath($updateForgotPasswordPath) ?: (class_exists(PathItem::class) ? new PathItem() : new LegacyPathItem()))
            ->withRef('ForgotPassword')
            ->withPost((class_exists(Operation::class) ? new Operation() : new LegacyOperation())
                ->withOperationId('postForgotPasswordToken')
                ->withTags(['Forgot password'])
                ->withResponses([
                    204 => [
                        'description' => 'Email address format valid, no matter if user exists or not',
                    ],
                    400 => [
                        'description' => 'Missing password parameter',
                    ],
                    404 => [
                        'description' => 'Token not found',
                    ],
                ])
                ->withSummary('Validates token')
                ->withParameters([
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
                        'in' => 'header',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ])
                ->withRequestBody((class_exists(RequestBody::class) ? new RequestBody() : new LegacyRequestBody())
                    ->withDescription('Reset password')
                    ->withRequired(true)
                    ->withContent(new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:reset',
                            ],
                        ],
                    ])
                    )
                )
            )
        );

        return $openApi;
    }
}
