<?php

namespace CoopTilleuls\ForgotPasswordBundle\Manager;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ForgotPasswordManager
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PasswordTokenManager
     */
    private $passwordTokenManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $userClass;

    /**
     * @var string
     */
    private $userFieldName;

    /**
     * @param TokenStorageInterface    $tokenStorage
     * @param RequestStack             $requestStack
     * @param PasswordTokenManager     $passwordTokenManager
     * @param EventDispatcherInterface $dispatcher
     * @param Registry                 $doctrine
     * @param string                   $userClass
     * @param string                   $userFieldName
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        PasswordTokenManager $passwordTokenManager,
        EventDispatcherInterface $dispatcher,
        Registry $doctrine,
        $userClass,
        $userFieldName
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->passwordTokenManager = $passwordTokenManager;
        $this->dispatcher = $dispatcher;
        $this->entityManager = $doctrine->getManager();
        $this->userClass = $userClass;
        $this->userFieldName = $userFieldName;
    }

    /**
     * @return bool
     */
    public function resetPassword()
    {
        // Authenticated user cannot ask to reset password
        if (null !== ($token = $this->tokenStorage->getToken()) && $token->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException();
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null === ($value = $currentRequest->get($this->userFieldName))) {
            return false;
        }

        /** @var UserInterface $user */
        if (null === ($user = $this->entityManager->getRepository($this->userClass)->findOneBy(
                [$this->userFieldName => $value]
            ))
        ) {
            return false;
        }

        // Generate password token
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::CREATE_TOKEN,
            new ForgotPasswordEvent($this->passwordTokenManager->createPasswordToken($user))
        );

        return true;
    }

    /**
     * @param AbstractPasswordToken $passwordToken
     *
     * @return bool
     */
    public function updatePassword(AbstractPasswordToken $passwordToken)
    {
        // Authenticated user cannot ask to reset password
        if (null !== ($token = $this->tokenStorage->getToken()) && $token->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException();
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null === ($password = $currentRequest->get('password'))) {
            return false;
        }

        // Update user password
        $this->dispatcher->dispatch(
            ForgotPasswordEvent::UPDATE_PASSWORD,
            new ForgotPasswordEvent($passwordToken, $password)
        );

        // Remove PasswordToken
        $this->entityManager->remove($passwordToken);
        $this->entityManager->flush();

        return true;
    }
}
