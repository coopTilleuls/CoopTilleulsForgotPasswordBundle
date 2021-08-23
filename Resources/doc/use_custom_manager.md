Use custom manager
------------------

By default, CoopTilleulsForgotPasswordBundle works with Doctrine ORM, but you're free to connect with any system.

## Create your custom manager

Supposing you want to use your custom entity manager, you'll have to create a service that will implement
`CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface`:

```php
// src/Manager/FooManager.php
namespace App\Manager;

use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;

class FooManager implements ManagerInterface
{
    private $foo;

    /**
     * {@inheritdoc}
     */
    public function findOneBy($class, array $criteria)
    {
        // Find & return an object of a specific class according to criteria
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        // Save PasswordToken object
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        // Remove PasswordToken object
    }
}
```

## Update configuration

Update your configuration to set your service as default one to use by CoopTilleulsForgotPasswordBundle:

```yml
# config/packages/coop_tilleuls_forgot_password.yaml
coop_tilleuls_forgot_password:
    # ...
    manager: 'App\Manager\FooManager'
```
