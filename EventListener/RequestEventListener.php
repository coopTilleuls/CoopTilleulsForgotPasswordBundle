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

use CoopTilleuls\ForgotPasswordBundle\Exception\InvalidJsonHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\NoParameterException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use Symfony\Component\HttpKernel\Event\KernelEvent;
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
     * @param string $userPasswordField
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

    public function decodeRequest(KernelEvent $event)
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

        $content = $request->getContent();
        $data = json_decode($content, true);
        if (!empty($content) && JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonHttpException();
        }
        if (!is_array($data) || empty($data)) {
            throw new NoParameterException();
        }

        $fieldName = key($data);
        if (empty($data[$fieldName])) {
            throw new MissingFieldHttpException($fieldName);
        }

        if ('coop_tilleuls_forgot_password.reset' === $routeName) {
            if (!in_array($fieldName, $this->authorizedFields, true)) {
                throw new UnauthorizedFieldException($fieldName);
            }
            $request->attributes->set('propertyName', $fieldName);
            $request->attributes->set('value', $data[$fieldName]);
        } else {
            if ($this->userPasswordField !== $fieldName) {
                throw new MissingFieldHttpException($this->userPasswordField);
            }

            $request->attributes->set($fieldName, $data[$fieldName]);
        }
    }

    public function getTokenFromRequest(KernelEvent $event)
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
