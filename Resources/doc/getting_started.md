Getting started with CoopTilleulsForgotPasswordBundle
-----------------------------------------------------

## Installation

Installing CoopTilleulsForgotPasswordBundle can be done easily through [Composer](https://getcomposer.org/):

```bash
composer require tilleuls/forgot-password-bundle
```

Register this bundle in your kernel:

```php
// app/AppKernel.php
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

### Load routing

Load routing:

```yml
# app/config/routing.yml
coop_tilleuls_forgot_password:
    resource: '@CoopTilleulsForgotPasswordBundle/Resources/config/routing.xml'
    prefix:   '/forgot_password'
```

This provides 2 main routes:
- `POST /forgot_password/`: receives user email (or custom field configured through `email_field`)
- `POST /forgot_password/{token}`: update user password (or custom field configured through `password_field`)

### Create your entity

CoopTilleulsForgotPasswordBundle provides an abstract _mapped superclass_, you'll have to create your own
`PasswordToken` entity for your project:

```php
namespace AppBundle\Entity;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Doctrine\ORM\Mapping as ORM;

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
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
```

### Configure your application

Enable required configuration:

```yml
# app/config/config.yml
coop_tilleuls_forgot_password:
    password_token_class: 'AppBundle\Entity\PasswordToken'
    user_class:           'AppBundle\Entity\User'
```

By default, this bundle will look for `email` field on user class to retrieve it, will generate a PasswordToken valid
for 1 day, and will set a `password` field when sent. You can customize those fields through complete configuration:

```yml
# app/config/config.yml
coop_tilleuls_forgot_password:
    password_token_class: 'AppBundle\Entity\PasswordToken'
    user_class:           'AppBundle\Entity\User'
    email_field:          'email'
    password_field:       'password'
    expires_in:           '1 day'
```

## Usage

CoopTilleulsForgotPasswordBundle provides 2 events allowing you to build your own business:
- `coop_tilleuls_forgot_password.create_token`: dispatched when user requests a new password (`POST /forgot_password/`)
- `coop_tilleuls_forgot_password.update_password`: dispatched when user has reset its password (`POST /forgot_password/{token}`)

Read full documentation about [usage](usage.md).

## Connect your manager

By default, CoopTilleulsForgotPasswordBundle works with Doctrine ORM, but you're free to connect with any system.

Read full documentation about [how to connect your manager](use_custom_manager.md).
