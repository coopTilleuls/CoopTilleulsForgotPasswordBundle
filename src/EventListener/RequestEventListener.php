<?php

/*
 * This file is part of the CoopTilleulsForgotPasswordBundle package.
 *
 * (c) Vincent CHALAMON <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace CoopTilleuls\ForgotPasswordBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Exception\InvalidJsonHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\MissingFieldHttpException;
use CoopTilleuls\ForgotPasswordBundle\Exception\NoParameterException;
use CoopTilleuls\ForgotPasswordBundle\Exception\UnauthorizedFieldException;
use CoopTilleuls\ForgotPasswordBundle\Manager\PasswordTokenManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class RequestEventListener
{
    use MainRequestTrait;

    public function __construct(private readonly PasswordTokenManager $passwordTokenManager, private readonly ProviderChainInterface $providerChain)
    {
    }

    public function decodeRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (!$this->isMainRequest($event) || !\in_array(
            $routeName,
            ['coop_tilleuls_forgot_password.reset', 'coop_tilleuls_forgot_password.update'], true
        )
        ) {
            return;
        }

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (!empty($content) && \JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonHttpException();
        }
        if (!\is_array($data) || empty($data)) {
            throw new NoParameterException();
        }

        $fieldName = key($data);
        if (empty($data[$fieldName])) {
            throw new MissingFieldHttpException($fieldName);
        }

        $provider = $this->providerChain->get($request->headers->get('FP-provider'));
        $request->attributes->set('provider', $provider);

        if ('coop_tilleuls_forgot_password.reset' === $routeName) {
            foreach ($data as $fieldName => $value) {
                if (\in_array($fieldName, $provider->getUserAuthorizedFields(), true)) {
                    $request->attributes->set('propertyName', $fieldName);
                    $request->attributes->set('value', $value);

                    return;
                }
            }

            throw new UnauthorizedFieldException($fieldName);
        }

        // if $routeName is 'coop_tilleuls_forgot_password.update'
        if (!\array_key_exists($userPasswordField = $provider->getUserPasswordField(), $data)) {
            throw new MissingFieldHttpException($userPasswordField);
        }

        $request->attributes->set('password', $data[$userPasswordField]);
    }

    public function getTokenFromRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (!$this->isMainRequest($event) || !\in_array(
            $routeName,
            ['coop_tilleuls_forgot_password.get_token', 'coop_tilleuls_forgot_password.update'], true
        )
        ) {
            return;
        }

        $provider = $this->providerChain->get($request->headers->get('FP-provider'));
        $token = $this->passwordTokenManager->findOneByToken($request->attributes->get('tokenValue'), $provider);

        if (null === $token || $token->isExpired()) {
            throw new NotFoundHttpException('Invalid token.');
        }

        $request->attributes->set('token', $token);
        $request->attributes->set('provider', $provider);
    }
}
