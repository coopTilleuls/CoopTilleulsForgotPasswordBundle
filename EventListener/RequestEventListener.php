<?php

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestEventListener
{
    private $userEmailField;
    private $userPasswordField;
    private $passwordTokenManager;
    private $validator;

    /**
     * @param string $userEmailField
     * @param string $userPasswordField
     * @param PasswordTokenManager $passwordTokenManager
     * @param ValidatorInterface $validator
     */
    public function __construct(
        $userEmailField,
        $userPasswordField,
        PasswordTokenManager $passwordTokenManager,
        ValidatorInterface $validator
    ) {
        $this->userEmailField = $userEmailField;
        $this->userPasswordField = $userPasswordField;
        $this->passwordTokenManager = $passwordTokenManager;
        $this->validator = $validator;
    }

    public function decodeRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');
        if (!$event->isMasterRequest() || !in_array(
                $routeName,
                ['coop_tilleuls_forgot_password.reset', 'coop_tilleuls_forgot_password.update']
            )
        ) {
            return;
        }

        $data = json_decode($request->getContent(), true);
        $fieldName = 'coop_tilleuls_forgot_password.reset' === $routeName ? $this->userEmailField : $this->userPasswordField;
        if (!isset($data[$fieldName]) || empty($data[$fieldName])) {
            throw new MissingFieldHttpException($fieldName);
        }
        $request->attributes->set($fieldName, $data[$fieldName]);
    }

    public function getTokenFromRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');
        if (!$event->isMasterRequest() || !in_array(
                $routeName,
                ['coop_tilleuls_forgot_password.get_token', 'coop_tilleuls_forgot_password.update']
            )
        ) {
            return;
        }

        $token = $this->passwordTokenManager->findOneByToken($request->get('tokenValue'));
        if (null === $token || 0 < $this->validator->validate($token)->count()) {
            throw new NotFoundHttpException('Invalid token.');
        }
        $request->attributes->set('token', $token);
    }
}
