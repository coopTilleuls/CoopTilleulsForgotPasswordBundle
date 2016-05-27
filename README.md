# ForgotPasswordBundle

Provides a 'forgot-password' feature for a REST API.

**This bundle is still in progress. You will be notified soon of its first release ;).**

Feel free to contribute on it !

## Installation

Install this bundle through [Composer](https://getcomposer.org/):

```bash
composer require --dev coopTilleuls/forgot-password-bundle
```

Update your `AppKernel.php` file:

```php
public function registerBundles()
{
    ...
    if ($this->getEnvironment() != 'prod') {
        ...
        $bundles[] = new ForgotPasswordBundle\ForgotPasswordBundle();
    }
}
```

Now load default routing:

```yml
# app/config/routing.yml
forgot_password:
    resource: "@ForgotPasswordBundle/Controller/"
    type:     annotation
    prefix:   /forgot_password
```

Finally, enable custom configuration:

```yml
# app/config.yml
forgot_password:
    password_token_class: 'AppBundle\Entity\PasswordToken'
    user_class: 'AppBundle\Entity\Person'
    user_field: 'email'
```
