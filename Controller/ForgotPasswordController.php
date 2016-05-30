<?php

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ForgotPasswordController
{
    private $tokenStorage;
    private $forgotPasswordManager;
    private $passwordTokenManager;
    private $userFieldName;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ForgotPasswordManager $forgotPasswordManager
     * @param PasswordTokenManager  $passwordTokenManager
     * @param string                $userFieldName
     */
    public function __construct(TokenStorageInterface $tokenStorage, ForgotPasswordManager $forgotPasswordManager, PasswordTokenManager $passwordTokenManager, $userFieldName)
    {
        $this->tokenStorage = $tokenStorage;
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->passwordTokenManager = $passwordTokenManager;
        $this->userFieldName = $userFieldName;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     *
     * @throws AccessDeniedHttpException
     */
    public function resetPasswordAction(Request $request)
    {
        // Authenticated user cannot ask to reset password
        $token = $this->tokenStorage->getToken();
        if ($token->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data[$this->userFieldName]) && true === $this->forgotPasswordManager->resetPassword($data[$this->userFieldName])) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->userFieldName => 'Invalid'], 400);
    }

    /**
     * @param string  $tokenValue
     * @param Request $request
     *
     * @return Response|JsonResponse
     */
    public function updatePasswordAction($tokenValue, Request $request)
    {
        // Authenticated user cannot ask to reset password
        $userToken = $this->tokenStorage->getToken();
        if (null !== $userToken && $userToken->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException();
        }

        $token = $this->passwordTokenManager->findOneByToken($tokenValue);
        if (null === $token) {
            throw new NotFoundHttpException('Invalid token.');
        }

        if ((new \DateTime()) > $token->getExpiresAt()) {
            throw new NotFoundHttpException('The token has expired.');
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['password']) && true === $this->forgotPasswordManager->updatePassword($token, $data['password'])) { // FIXME: password field should be configurable
            return new Response('', 204);
        }

        return new JsonResponse(['password' => 'Invalid password'], 400);
    }
}
