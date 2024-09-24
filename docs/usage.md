# Usage

This bundle provides 3 events allowing you to build your own business:

- `coop_tilleuls_forgot_password.create_token`: dispatched when user requests a new password (`POST /forgot-password/`)
- `coop_tilleuls_forgot_password.update_password`: dispatched when user has reset its
  password (`POST /forgot-password/{tokenValue}`)
- `coop_tilleuls_forgot_password.user_not_found`: dispatched when a user was not found (`POST /forgot-password/`)

## Send email on user request

On the first user story, user will send its identifier (email, username...), you'll have to send a custom email
allowing him to reset its password using a valid PasswordToken.

```php
// src/EventSubscriber/ForgotPasswordEventSubscriber.php
namespace App\EventSubscriber;

// ...
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MailerInterface $mailer, private readonly Environment $twig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 4.3 and inferior, use 'coop_tilleuls_forgot_password.create_token' event name
            CreateTokenEvent::class => 'onCreateToken',
        ];
    }

    public function onCreateToken(CreateTokenEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();

        $message = (new Email())
            ->from('no-reply@example.com')
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->html($this->twig->render(
                'App:ResetPassword:mail.html.twig',
                [
                    'reset_password_url' => sprintf('https://www.example.com/forgot-password/%s', $passwordToken->getToken()),
                ]
            ));
        $this->mailer->send($message);
    }
}
```

Your app is ready to receive a JSON request like:

```json
{
    "email": "foo@example.com"
}
```

## Update the user password

On the second user story, user will send its new password, and you'll have to encode it and save it.

```php
// src/EventSubscriber/ForgotPasswordEventSubscriber.php
namespace App\EventSubscriber;

// ...
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserManager $userManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 4.3 and inferior, use 'coop_tilleuls_forgot_password.update_password' event name
            UpdatePasswordEvent::class => 'onUpdatePassword',
        ];
    }

    public function onUpdatePassword(UpdatePasswordEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        $user->setPlainPassword($event->getPassword());
        $this->userManager->updateUser($user);
    }
}
```

Your app is ready to receive a request like:

```json
{
    "password": "P4$$w0rd"
}
```

### Validate the user password

Chances are that you want to ensure the new password is strong enough.

```php
// src/Entity/User.php
namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\PasswordStrength]
    protected $rawPassword;
}
```

Now, you can use the very same event to validate the User.

```php
// src/EventSubscriber/ForgotPasswordEventSubscriber.php

public function onUpdatePassword(UpdatePasswordEvent $event): void
{
    $passwordToken = $event->getPasswordToken();
    $user = $passwordToken->getUser();
    $user->setPlainPassword($event->getPassword());
    
    // ApiPlatform\Validator\ValidatorInterface
    $this->validator->validate($user); // throws an Exception if invalid
    
    /*
     * // Symfony\Component\Validator\Validator\ValidatorInterface
     * $constraintViolationList = $this->validator->validate($user); // returns a ConstraintViolationListInterface which is a \Traversable, \Countable and \ArrayAccess
     * 
     * // TODO: handle when the list is not empty
     */
    
    $this->userManager->updateUser($user);
}
```

Please note that when using API Platform validator, there is a slight difference between version 3.3 and 3.4+.  

**In version 3.3 and lower**, the validation system overwrite Symfony's. In case of a constraint violation Exception thrown, it will always respond in JSON with Hydra / JsonLD / JsonProblem, according to your configuration. This, even if the Request has been sent through a classic form. _You might want to prefer one or the other accordingly to your situation._  

**In version 3.4 and above**, this unwanted behaviour has been fixed and API Platform's validation system will check if the object (here the user) is an API Platform resource. If not, It will fallback to Symfony's error system, as it should. _Using API Platform validator is then completely fine._

## Use your own business rules when the user is not found

On the third user story, user was not found, you can listen to this event and use your own rules.

```php
// src/EventSubscriber/ForgotPasswordEventSubscriber.php
namespace App\EventSubscriber;

// ...
use CoopTilleuls\ForgotPasswordBundle\Event\UserNotFoundEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserManager $userManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 4.3 and inferior, use 'coop_tilleuls_forgot_password.user_not_found' event name
            UserNotFoundEvent::class => 'onUserNotFound',
        ];
    }

    public function onUserNotFound(UserNotFoundEvent $event): void
    {
         $context = $event->getContext();
         // ...
    }
}
```
