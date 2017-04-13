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

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordController
{
    private $forgotPasswordManager;
    private $normalizer;
    private $groups;

    /**
     * @param ForgotPasswordManager $forgotPasswordManager
     * @param NormalizerInterface   $normalizer
     * @param array                 $groups
     */
    public function __construct(
        ForgotPasswordManager $forgotPasswordManager,
        NormalizerInterface $normalizer,
        array $groups
    ) {
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->normalizer = $normalizer;
        $this->groups = $groups;
    }

    /**
     * @param string $email
     *
     * @return Response
     */
    public function resetPasswordAction($email)
    {
        $this->forgotPasswordManager->resetPassword($email);

        return new Response('', 204);
    }

    /**
     * @param AbstractPasswordToken $token
     *
     * @return JsonResponse
     */
    public function getTokenAction(AbstractPasswordToken $token)
    {
        return new JsonResponse(
            $this->normalizer->normalize($token, 'json', $this->groups ? ['groups' => $this->groups] : [])
        );
    }

    /**
     * @param AbstractPasswordToken $token
     * @param string                $password
     *
     * @return Response
     */
    public function updatePasswordAction(AbstractPasswordToken $token, $password)
    {
        $this->forgotPasswordManager->updatePassword($token, $password);

        return new Response('', 204);
    }
}
