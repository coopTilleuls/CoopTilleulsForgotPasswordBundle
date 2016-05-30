# CoopTilleulsForgotPasswordBundle

Provides a 'forgot password' feature for a REST API.

**This bundle is still in progress. You will be notified soon of its first release ;).**

Feel free to contribute on it!

[![Build Status](https://travis-ci.org/coopTilleuls/ForgotPasswordBundle.svg?branch=master)](https://travis-ci.org/coopTilleuls/ForgotPasswordBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/79b794ea-1f79-49fc-b27a-b0bf86afd30c/mini.png)](https://insight.sensiolabs.com/projects/79b794ea-1f79-49fc-b27a-b0bf86afd30c)

## Installation

Install this bundle through [Composer](https://getcomposer.org/):

```bash
composer require tilleuls/forgot-password-bundle
```

Update your `AppKernel.php` file:

```php
public function registerBundles()
{
    $bundles = [
        new CoopTilleuls\ForgotPasswordBundle\CoopTilleulsForgotPasswordBundle(),
        // ...
    ];

    // ...
}
```

## Configuration

Load routing:

```yml
# app/config/routing.yml
forgot_password:
    resource: '@CoopTilleulsForgotPasswordBundle/Controller/'
    type:     annotation
    prefix:   /forgot_password
```

Enable configuration:

```yml
# app/config.yml
forgot_password:
    password_token_class: 'AppBundle\Entity\PasswordToken'
    user_class:           'AppBundle\Entity\User'
    user_field:           'email'
```

Create your own `PasswordToken` entity:

```php
namespace AppBundle\Entity;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 */
class PasswordToken extends AbstractPasswordToken
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }
}
```

## Usage

This bundle provides 2 main routes:
- `POST /forgot_password/`: receives user email (or another field customized through `user_field` configuration key)
- `POST /forgot_password/{token}`: update user password

### Send email on user request

On the first user story, user will send its identifier (email, username...), and you'll have to send a custom email
allowing him to reset its password.

Create an event listener listening to `forgot_password.create_token` event:

```yml
# AppBundle/Resources/config/services.yml
services:
    app.listener.forgot_password:
        # ...
        tags:
            - { name: kernel.event_listener, event: forgot_password.create_token, method: onCreateToken }
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

Your app is ready to receive a request like:

```json
{
    "email": "foo@example.com"
}
```

### Update the password of the user

On the second user story, user will send its new password, and you'll have to encode it and save it: this is your own
business.

Update your event listener listening to `forgot_password.update_password` event:

```yml
# app/config/services.yml
services:
    app.listener.forgot_password:
        # ...
        tags:
            # ...
            - { name: kernel.event_listener, event: forgot_password.update_password, method: onUpdatePassword }
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

## Credits

Created by [Vincent Chalamon](http://vincent-chalamon.fr/) for Les-Tilleuls.coop.
