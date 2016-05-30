<?php

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ForgotPasswordController
{
    private $forgotPasswordManager;
    private $passwordTokenManager;
    private $userFieldName;

    /**
     * @param ForgotPasswordManager $forgotPasswordManager
     * @param PasswordTokenManager  $passwordTokenManager
     * @param string                $userFieldName
     */
    public function __construct(ForgotPasswordManager $forgotPasswordManager, PasswordTokenManager $passwordTokenManager, $userFieldName)
    {
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->passwordTokenManager = $passwordTokenManager;
    }

    /**
     * @return Response|JsonResponse
     */
    public function resetPasswordAction()
    {
        if (true === $this->forgotPasswordManager->resetPassword()) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->userFieldName => 'Invalid'], 400);
    }

    /**
     * @param string $tokenValue
     *
     * @return Response|JsonResponse
     */
    public function updatePasswordAction($tokenValue)
    {
        $token = $this->passwordTokenManager->findOneByToken($tokenValue);

        if (null === $token) {
            throw new NotFoundHttpException('Invalid token.');
        }

        if ((new \DateTime()) > $token->getExpiresAt()) {
            throw new NotFoundHttpException('The token has expired.');
        }

        if (true === $this->forgotPasswordManager->updatePassword($token)) {
            return new Response('', 204);
        }

        return new JsonResponse(['password' => 'Invalid password'], 400);
    }
}
