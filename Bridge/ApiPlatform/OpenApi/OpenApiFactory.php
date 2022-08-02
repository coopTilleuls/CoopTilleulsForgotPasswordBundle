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
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    private $decorated;
    private $router;

    public function __construct(OpenApiFactoryInterface $decorated, RouterInterface $router)
    {
        $this->decorated = $decorated;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        $routes = $this->router->getRouteCollection();
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();
        $paths = $openApi->getPaths();

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

        $resetForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.reset')->getPath();
        $paths->addPath($resetForgotPasswordPath, ($paths->getPath($resetForgotPasswordPath) ?: new PathItem())
            ->withRef('ForgotPassword')
            ->withPost((new Operation())
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
                ->withRequestBody(new RequestBody(
                    'Request a new password',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:request',
                            ],
                        ],
                    ])
                ))
            )
        );

        $getForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.get_token')->getPath();
        $paths->addPath($getForgotPasswordPath, ($paths->getPath($getForgotPasswordPath) ?: new PathItem())
            ->withRef('ForgotPassword')
            ->withGet((new Operation())
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
                ])
            )
        );

        $updateForgotPasswordPath = $routes->get('coop_tilleuls_forgot_password.update')->getPath();
        $paths->addPath($updateForgotPasswordPath, ($paths->getPath($updateForgotPasswordPath) ?: new PathItem())
            ->withRef('ForgotPassword')
            ->withPost((new Operation())
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
                ])
                ->withRequestBody(new RequestBody(
                    'Reset password',
                    new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ForgotPassword:reset',
                            ],
                        ],
                    ])
                ))
            )
        );

        return $openApi;
    }
}
