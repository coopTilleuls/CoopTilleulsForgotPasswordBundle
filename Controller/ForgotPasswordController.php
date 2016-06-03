<?php

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ForgotPasswordController
{
    private $forgotPasswordManager;
    private $passwordTokenManager;
    private $emailFieldName;
    private $passwordFieldName;

    /**
     * @param ForgotPasswordManager $forgotPasswordManager
     * @param PasswordTokenManager  $passwordTokenManager
     * @param string                $emailFieldName
     * @param string                $passwordFieldName
     */
    public function __construct(
        ForgotPasswordManager $forgotPasswordManager,
        PasswordTokenManager $passwordTokenManager,
        $emailFieldName,
        $passwordFieldName
    ) {
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->passwordTokenManager = $passwordTokenManager;
        $this->emailFieldName = $emailFieldName;
        $this->passwordFieldName = $passwordFieldName;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function resetPasswordAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data[$this->emailFieldName]) && true === $this->forgotPasswordManager->resetPassword(
                $data[$this->emailFieldName]
            )
        ) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->emailFieldName => 'Invalid'], 400);
    }

    /**
     * @param string  $tokenValue
     * @param Request $request
     *
     * @return Response|JsonResponse
     *
     * @throws NotFoundHttpException
     */
    public function updatePasswordAction($tokenValue, Request $request)
    {
        $token = $this->passwordTokenManager->findOneByToken($tokenValue);
        if (null === $token) {
            throw new NotFoundHttpException('Invalid token.');
        }

        if ((new \DateTime()) > $token->getExpiresAt()) {
            throw new NotFoundHttpException('The token has expired.');
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data[$this->passwordFieldName]) && true === $this->forgotPasswordManager->updatePassword(
                $token,
                $data[$this->passwordFieldName]
            )
        ) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->passwordFieldName => 'Invalid'], 400);
    }
}
