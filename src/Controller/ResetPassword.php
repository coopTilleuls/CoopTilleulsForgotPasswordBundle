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

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ResetPassword
{
    public function __construct(private readonly ForgotPasswordManager $forgotPasswordManager)
    {
    }

    /**
     * @param string $propertyName
     * @param string $value
     *
     * @return Response
     */
    public function __invoke($propertyName, $value, ProviderInterface $provider)
    {
        $this->forgotPasswordManager->resetPassword($propertyName, $value, $provider);

        return new Response('', 204);
    }
}
