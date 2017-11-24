<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\NoParametersException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class RequestEventListener
{
    private $authorizedFields;
    private $userPasswordField;
    private $passwordTokenManager;

    /**
     * @param array $authorizedFields
     * @param string $userPasswordField
     * @param PasswordTokenManager $passwordTokenManager
     */
    public function __construct(
        array $authorizedFields,
        $userPasswordField,
        PasswordTokenManager $passwordTokenManager
    ) {
        $this->authorizedFields = $authorizedFields;
        $this->userPasswordField = $userPasswordField;
        $this->passwordTokenManager = $passwordTokenManager;
    }

    public function decodeRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (!$event->isMasterRequest() || !in_array(
                $routeName,
                ['coop_tilleuls_forgot_password.reset', 'coop_tilleuls_forgot_password.update']
            )
        ) {
            return;
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data)) {
            throw new NoParametersException();
        }

        $fieldName = key($data);
        if (empty($data[$fieldName])) {
            throw new MissingFieldHttpException($fieldName);
        }

        if ('coop_tilleuls_forgot_password.reset' === $routeName) {
            if (!in_array($fieldName, $this->authorizedFields)) {
                throw new UnauthorizedFieldException($fieldName);
            }
            $request->attributes->set('propertyName', $fieldName);
            $request->attributes->set('value', $data[$fieldName]);
        } else {
            $request->attributes->set($fieldName, $data[$fieldName]);
        }
    }

    public function getTokenFromRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (!$event->isMasterRequest() || !in_array(
                $routeName,
                ['coop_tilleuls_forgot_password.get_token', 'coop_tilleuls_forgot_password.update']
            )
        ) {
            return;
        }

        $token = $this->passwordTokenManager->findOneByToken($request->attributes->get('tokenValue'));
        if (null === $token || $token->isExpired()) {
            throw new NotFoundHttpException('Invalid token.');
        }
        $request->attributes->set('token', $token);
    }
}
