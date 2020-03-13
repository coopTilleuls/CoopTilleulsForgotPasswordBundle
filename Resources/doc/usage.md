Usage
-----

CoopTilleulsForgotPasswordBundle provides 2 events allowing you to build your own business:
- `coop_tilleuls_forgot_password.create_token`: dispatched when user requests a new password (`POST /forgot_password/`)
- `coop_tilleuls_forgot_password.update_password`: dispatched when user has reset its password (`POST /forgot_password/{token}`)

## Send email on user request

On the first user story, user will send its identifier (email, username...), you'll have to send a custom email
allowing him to reset its password using a valid PasswordToken.

Create an event listener listening to `coop_tilleuls_forgot_password.create_token` event:

```yml
# AppBundle/Resources/config/services.yml
services:
    app.listener.forgot_password:
        # ...
        tags:
            - { name: kernel.event_listener, event: coop_tilleuls_forgot_password.create_token, method: onCreateToken }
```

```php
namespace AppBundle/Event;

// ...
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;

class ForgotPasswordEventListener
{
    // ...

    /**
     * @param ForgotPasswordEvent $event
     */
    public function onCreateToken(ForgotPasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();

        $swiftMessage = new \Swift_Message(
            'Reset of your password',
            $this->templating->render(
                'AppBundle:ResetPassword:mail.html.twig',
                [
                    'reset_password_url' => sprintf('http://www.example.com/forgot-password/%s', $passwordToken->getToken()),
                ]
            )
        );

        $swiftMessage->setFrom('no-reply@example.com');
        $swiftMessage->setTo($user->getEmail());
        $swiftMessage->setContentType('text/html');
        if (0 === $this->mailer->send($swiftMessage)) {
            throw new \RuntimeException('Unable to send email');
        }
    }
}
```

Your app is ready to receive a JSON request like:

```json
{
    "email": "foo@example.com"
}
```

## Update the password of the user

On the second user story, user will send its new password, and you'll have to encode it and save it.

Update your event listener listening to `coop_tilleuls_forgot_password.update_password` event:

```yml
# app/config/services.yml
services:
    app.listener.forgot_password:
        # ...
        tags:
            # ...
            - { name: kernel.event_listener, event: coop_tilleuls_forgot_password.update_password, method: onUpdatePassword }
```

```php
namespace AppBundle/Event;

// ...
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;

class ForgotPasswordEventListener
{
    // ...
    /**
     * @param ForgotPasswordEvent $event
     */
    public function onUpdatePassword(ForgotPasswordEvent $event)
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
