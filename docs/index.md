# Getting started with CoopTilleulsForgotPasswordBundle

## Installation

Installing this bundle can be done easily through [Composer](https://getcomposer.org/):

```shell
composer require tilleuls/forgot-password-bundle
```

**If you're using [Flex](https://github.com/symfony/flex), all configuration is already done. You can customize it in
`config/packages/coop_tilleuls_forgot_password.yaml` file.** You can directly go to
[Create your entity](#create-your-entity) chapter.

Register this bundle in your kernel:

```php
// config/bundles.php
return [
    // ...
    CoopTilleuls\ForgotPasswordBundle\CoopTilleulsForgotPasswordBundle::class => ['all' => true],
];
```

## Configuration

### Load routing

Load routing:

```yaml
# config/routes/coop_tilleuls_forgot_password.yaml
coop_tilleuls_forgot_password:
    resource: .
    type: coop_tilleuls_forgot_password
    prefix: '/forgot-password'
```

It provides the following routes:

- `POST /forgot-password/`: receives user email (or custom field configured through `email_field`)
- `GET /forgot-password/{tokenValue}`: validates the token and returns it (
  cf. [Overriding the GET /forgot-password/{tokenValue} response](#overriding-the-get-forgot-passwordtoken-response))
- `POST /forgot-password/{tokenValue}`: update user password (or custom field configured through `password_field`)

### Create your entity

This bundle provides an abstract _mapped superclass_, you'll have to create your own `PasswordToken` entity for your
project:

```php
// src/Entity/PasswordToken.php
namespace App\Entity;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PasswordToken extends AbstractPasswordToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
```

### Configure your application

By default, this bundle will look for `email` field on user class to retrieve it, will generate a PasswordToken valid
for 1 day, and will set a `password` field when sent. Here is the default configuration:

```yaml
# config/packages/coop_tilleuls_forgot_password.yaml
coop_tilleuls_forgot_password:
    password_token:
        class: 'App\Entity\PasswordToken' # Token class fully qualified name (required)
        expires_in: '1 day'               # Token duration (optional, default value)
        user_field: 'user'                # User property in token class (optional, default value)
        serialization_groups: [ ]         # Serialization groups used in GET /forgot-password/{tokenValue} (optional, default value)
    user:
        class: 'App\Entity\User'          # User class fully qualified name (required)
        email_field: 'email'              # Email property in user class (optional, default value)
        password_field: 'password'        # Password property in user class (optional, default value)
        authorized_fields: [ 'email' ]    # User properties authorized to reset the password (optional, default value)
    use_jms_serializer: false             # Switch between symfony's serializer component or JMS Serializer
```

Update your security to allow anonymous users to reset their password:

```yaml
# config/packages/security.yaml
security:
    # ...
    firewalls:
        # ...
        main:
            # ...
            lazy: true

    access_control:
        - { path: '^/forgot-password', role: PUBLIC_ACCESS }
        # ...
```

## Overriding the GET /forgot-password/{tokenValue} response

By default, when you send a GET /forgot-password/{tokenValue} request, it serializes the token object in JSON, including the
User object through the relationship, using the `coop_tilleuls_forgot_password.password_token.serialization_groups`
configuration option.

If you want, for instance, to return an empty response, you can easily override this route by your own:

```yaml
# config/routes/coop_tilleuls_forgot_password.yaml
# ...

coop_tilleuls_forgot_password.get_token:
    path: /forgot-password/{tokenValue}
    methods: [ GET ]
    defaults:
        _controller: App\Controller\GetTokenController
```

```php
# src/Controller/GetTokenController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

final class GetTokenController
{
    public function __invoke(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
```

## Ensure user is not authenticated

When a user requests a new password, or resets it, it shouldn't be authenticated. But this is part of your own
application.

Read full documentation about [how to ensure user is not authenticated](user_not_authenticated.md).

## Usage

This bundle provides 3 events allowing you to build your own business:

- `coop_tilleuls_forgot_password.create_token`: dispatched when a user requests a new
  password (`POST /forgot-password/`)
- `coop_tilleuls_forgot_password.update_password`: dispatched when a user has reset its
  password (`POST /forgot-password/{tokenValue}`)
- `coop_tilleuls_forgot_password.user_not_found`: dispatched when a user was not found (`POST /forgot-password/`)

Read full documentation about [usage](usage.md).

## Connect your manager

By default, this bundle works with Doctrine ORM, but you're free to connect with any system.

Read full documentation about [how to connect your manager](use_custom_manager.md).
