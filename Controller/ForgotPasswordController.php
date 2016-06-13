<?php

namespace CoopTilleuls\ForgotPasswordBundle\Controller;

use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ForgotPasswordController
{
    private $forgotPasswordManager;
    private $passwordTokenManager;
    private $validator;
    private $normalizer;
    private $groups;
    private $userEmailField;
    private $userPasswordField;

    /**
     * @param ForgotPasswordManager $forgotPasswordManager
     * @param PasswordTokenManager  $passwordTokenManager
     * @param ValidatorInterface    $validator
     * @param NormalizerInterface   $normalizer
     * @param array                 $groups
     * @param string                $userEmailField
     * @param string                $userPasswordField
     */
    public function __construct(
        ForgotPasswordManager $forgotPasswordManager,
        PasswordTokenManager $passwordTokenManager,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer,
        array $groups,
        $userEmailField,
        $userPasswordField
    ) {
        $this->forgotPasswordManager = $forgotPasswordManager;
        $this->passwordTokenManager = $passwordTokenManager;
        $this->validator = $validator;
        $this->normalizer = $normalizer;
        $this->groups = $groups;
        $this->userEmailField = $userEmailField;
        $this->userPasswordField = $userPasswordField;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function resetPasswordAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data[$this->userEmailField]) && true === $this->forgotPasswordManager->resetPassword(
                $data[$this->userEmailField]
            )
        ) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->userEmailField => 'Invalid'], 400);
    }

    /**
     * @param string $tokenValue
     *
     * @return Response|JsonResponse
     *
     * @throws NotFoundHttpException
     */
    public function getTokenAction($tokenValue)
    {
        $token = $this->passwordTokenManager->findOneByToken($tokenValue);
        if (null === $token || 0 < $this->validator->validate($token)->count()) {
            throw new NotFoundHttpException('Invalid token.');
        }

        return new JsonResponse(
            $this->normalizer->normalize($token, 'json', $this->groups ? ['groups' => $this->groups] : [])
        );
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
        if (null === $token || 0 < $this->validator->validate($token)->count()) {
            throw new NotFoundHttpException('Invalid token.');
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data[$this->userPasswordField]) && true === $this->forgotPasswordManager->updatePassword(
                $token,
                $data[$this->userPasswordField]
            )
        ) {
            return new Response('', 204);
        }

        return new JsonResponse([$this->userPasswordField => 'Invalid'], 400);
    }
}
