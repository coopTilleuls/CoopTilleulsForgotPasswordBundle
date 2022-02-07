Usage with API Platform
-----------------------------------------------------

## Decorate Swagger

This bundle already provides a decorator that you must plug to your OpenApi documentation.

In order to do so, add the following lines to your services.yaml

```yml
# config/services.yaml
services:
# ...
    CoopTilleuls\ForgotPasswordBundle\Bridge\ApiPlatform\OpenApi\OpenApiFactory:
        decorates: 'api_platform.openapi.factory'
        autoconfigure: false
```
