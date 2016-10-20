<?php

/*
 * This file is part of the ForgotPasswordBundle package.
 *
 * (c) Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\EventListener;

use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordEventListener
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Swift_Mailer   $mailer
     * @param EngineInterface $templating
     * @param Registry        $doctrine
     */
    public function __construct(\Swift_Mailer $mailer, EngineInterface $templating, Registry $doctrine)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->entityManager = $doctrine->getManager();
    }

    /**
     * @param ForgotPasswordEvent $event
     */
    public function onCreateToken(ForgotPasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();

        $swiftMessage = new \Swift_Message(
            'Réinitialisation de votre mot de passe',
            $this->templating->render(
                'CoopTilleulsTestBundle:ResetPassword:mail.html.twig',
                ['token' => $passwordToken->getToken()]
            )
        );
        $swiftMessage->setFrom('no-reply@example.com');
        $swiftMessage->setTo($user->getEmail());
        $swiftMessage->setContentType('text/html');
        if (0 === $this->mailer->send($swiftMessage)) {
            throw new \RuntimeException('Unable to send email');
        }
    }

    /**
     * @param ForgotPasswordEvent $event
     */
    public function onUpdatePassword(ForgotPasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();
        $user->setPassword($event->getPassword());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
