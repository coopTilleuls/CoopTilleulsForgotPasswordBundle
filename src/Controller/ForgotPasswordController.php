<?php

namespace ForgotPasswordBundle\Controller;

use ForgotPasswordBundle\Entity\AbstractPasswordToken;
use ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route(service="forgot_password.controller.forgot_password")
 */
class ForgotPasswordController
{
    /**
     * @var ForgotPasswordManager
     */
    private $forgotPasswordManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $userFieldName;

    /**
     * @param ForgotPasswordManager         $forgotPasswordManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param string                        $userFieldName
     */
    public function __construct(
        ForgotPasswordManager $forgotPasswordManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        $userFieldName
    ) {
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->userFieldName = $userFieldName;
    }

    /**
     * @Route(name="forgot_password.reset")
     * @Method({"POST"})
     *
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
     * @ParamConverter(name="token", class="ForgotPasswordBundle\Entity\AbstractPasswordToken")
     *
     * @Route("/{token}", name="forgot_password.update")
     * @Method({"POST"})
     *
     * @param AbstractPasswordToken $token
     *
     * @return Response|JsonResponse
     */
    public function updatePasswordAction(AbstractPasswordToken $token)
    {
        if (true === $this->forgotPasswordManager->updatePassword($token)) {
            return new Response('', 204);
        }

        return new JsonResponse(['password' => 'Invalid password'], 400);
    }
}
