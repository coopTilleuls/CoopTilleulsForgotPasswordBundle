# Use custom token generator

By default, this bundle works uses [`bin2hex`](https://www.php.net/bin2hex) combined with
[`random_bytes`](https://www.php.net/random_bytes) to generate the token, but you're free to create your own
TokenGenerator to create your token.

## Create your custom token generator

Supposing you want to generate your own token, you'll have to create a service that will implement
`CoopTilleuls\ForgotPasswordBundle\TokenGenerator\TokenGeneratorInterface`:

```php
// src/TokenGenerator/FooTokenGenerator.php
namespace App\TokenGenerator;

use CoopTilleuls\ForgotPasswordBundle\TokenGenerator\TokenGeneratorInterface;

final class FooTokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        // generate your own token and return it as string
    }
}
```

## Update configuration

Update your configuration to set your service as default one to use by this bundle:

```yaml
# config/packages/coop_tilleuls_forgot_password.yaml
coop_tilleuls_forgot_password:
    # ...
    token_generator: 'App\TokenGenerator\FooTokenGenerator'
```
