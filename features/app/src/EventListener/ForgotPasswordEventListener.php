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

namespace App\EventListener;

use App\Entity\User;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * @author Vincent CHALAMON <vincent@les-tilleuls.coop>
 */
final class ForgotPasswordEventListener implements EventSubscriberInterface
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var EngineInterface|Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(MailerInterface $mailer, $twig, Registry $doctrine)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->entityManager = $doctrine->getManager();
    }

    public static function getSubscribedEvents()
    {
        return [
            CreateTokenEvent::class => 'onCreateToken',
            UpdatePasswordEvent::class => 'onUpdatePassword',
        ];
    }

    public function onCreateToken(CreateTokenEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();

        $message = (new Email())
            ->from('no-reply@example.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->twig->render('ResetPassword/mail.html.twig', ['token' => $passwordToken->getToken()]));
        $this->mailer->send($message);
    }

    public function onUpdatePassword(UpdatePasswordEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();
        $user->setPassword($event->getPassword());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
