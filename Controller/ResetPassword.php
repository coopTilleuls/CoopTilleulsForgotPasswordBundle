<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ResetPassword
{
    private $forgotPasswordManager;

    public function __construct(ForgotPasswordManager $forgotPasswordManager)
    {
        $this->forgotPasswordManager = $forgotPasswordManager;
    }

    /**
     * @param string $propertyName
     * @param string $value
     *
     * @return Response
     */
    public function __invoke($propertyName, $value)
    {
        if(false === $this->forgotPasswordManager->resetPassword($propertyName, $value)) {
            return new Response('User not found', 404);
        }

        return new Response('Token sent', 201);
    }
}
