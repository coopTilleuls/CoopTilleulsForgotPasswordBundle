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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * @deprecated Use invokable controllers instead
 */
final class ForgotPasswordController
{
    private $getToken;
    private $updatePassword;
    private $resetPassword;

    public function __construct(GetToken $getToken, UpdatePassword $updatePassword, ResetPassword $resetPassword)
    {
        $this->getToken = $getToken;
        $this->updatePassword = $updatePassword;
        $this->resetPassword = $resetPassword;
    }

    /**
     * @param string $propertyName
     * @param string $value
     *
     * @return Response
     */
    public function resetPasswordAction($propertyName, $value, ProviderInterface $provider)
    {
        $resetPassword = $this->resetPassword;

        return $resetPassword($propertyName, $value, $provider);
    }

    /**
     * @return JsonResponse
     */
    public function getTokenAction(AbstractPasswordToken $token, ProviderInterface $provider)
    {
        $getToken = $this->getToken;

        return $getToken($token, $provider);
    }

    /**
     * @param string $password
     *
     * @return Response
     */
    public function updatePasswordAction(AbstractPasswordToken $token, $password, ProviderInterface $provider)
    {
        $updatePassword = $this->updatePassword;

        return $updatePassword($token, $password, $provider);
    }
}
