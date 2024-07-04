# Use custom manager

By default, this bundles works with Doctrine ORM, but you're free to connect with any system.

## Create your custom manager

Supposing you want to use your custom entity manager, you'll have to create a service that will implement
`CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface`:

```php
// src/Manager/FooManager.php
namespace App\Manager;

use App\Entity\PasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\Bridge\ManagerInterface;

final class FooManager implements ManagerInterface
{
    public function findOneBy($class, array $criteria): ?PasswordToken
    {
        // Find & return an object of a specific class according to criteria
    }

    public function persist($object): void
    {
        // Save PasswordToken object
    }

    public function remove($object): void
    {
        // Remove PasswordToken object
    }
}
```

## Update configuration

Update your configuration to set your service as default one to use by this bundle:

```yaml
# config/packages/coop_tilleuls_forgot_password.yaml
coop_tilleuls_forgot_password:
    # ...

    providers:
        app_user_provider: # this is exemple of provider
            manager: 'App\Manager\FooManager'
```
