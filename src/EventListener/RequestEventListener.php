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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderFactoryInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class RequestEventListener
{
    use MainRequestTrait;

    private $passwordTokenManager;
    private ProviderFactoryInterface $providerFactory;

    public function __construct(
        PasswordTokenManager $passwordTokenManager,
        ProviderFactoryInterface $providerFactory
    ) {
        $this->passwordTokenManager = $passwordTokenManager;
        $this->providerFactory = $providerFactory;
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

        $provider = $this->providerFactory->get($data['provider'] ?? null);

        if ('coop_tilleuls_forgot_password.reset' === $routeName) {
            $request->attributes->set('providerName', $data['provider'] ?? null);

            unset($data['provider']);

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

        if ('coop_tilleuls_forgot_password.get_token' === $routeName) {
            $provider = $this->providerFactory->get($request->headers->get('X-provider') ?: null);
        } else {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            $provider = $this->providerFactory->get($data['provider'] ?? null);
        }

        $token = $this->passwordTokenManager->findOneByToken($provider->getPasswordTokenClass(), $request->attributes->get('tokenValue'));

        if (null === $token || $token->isExpired()) {
            throw new NotFoundHttpException('Invalid token.');
        }

        $request->attributes->set('token', $token);
        if ('coop_tilleuls_forgot_password.get_token' === $routeName) {
            $request->attributes->set('provider', $provider);
        }
    }
}
