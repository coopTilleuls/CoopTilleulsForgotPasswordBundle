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

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class UpdatePassword
{
    private $forgotPasswordManager;

    public function __construct(ForgotPasswordManager $forgotPasswordManager)
    {
        $this->forgotPasswordManager = $forgotPasswordManager;
    }

    /**
     * @param string $password
     *
     * @return Response
     */
    public function __invoke(AbstractPasswordToken $token, $password)
    {
        $this->forgotPasswordManager->updatePassword($token, $password);

        return new Response('', 204);
    }
}
