<?php

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(service="forgot_password.controller.forgot_password")
 */
class ForgotPasswordController
{
    private $forgotPasswordManager;
    private $userFieldName;

    /**
     * @param ForgotPasswordManager         $forgotPasswordManager
     * @param string                        $userFieldName
     */
    public function __construct(ForgotPasswordManager $forgotPasswordManager, $userFieldName)
    {
        $this->forgotPasswordManager = $forgotPasswordManager;
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
     * @ParamConverter(name="token", class="CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken")
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
