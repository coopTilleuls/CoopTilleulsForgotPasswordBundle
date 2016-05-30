<?php

namespace ForgotPasswordBundle\Tests\TestBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use ForgotPasswordBundle\Event\ForgotPasswordEvent;
use ForgotPasswordBundle\Tests\TestBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class ForgotPasswordEventListener
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
                'TestBundle:ResetPassword:mail.html.twig',
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
