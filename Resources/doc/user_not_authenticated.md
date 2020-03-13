Ensure user is not authenticated
--------------------------------

When a user requests a new password, or reset it, user shouldn't be authenticated. But this part is part of your own
application.

Create an EventListener and listen to `kernel.request` event:

```php
namespace AppBundle\Event;

// ...
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class ForgotPasswordEventListener
{
    // ...
    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()
            || !preg_match('/^coop_tilleuls_forgot_password/i', $event->getRequest()->get('_route'))
        ) {
            return;
        }

        // User should not be authenticated on forgot password
        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException;
        }
    }
}
```

Register this service:

```yml
# AppBundle/Resources/config/services.yml
services:
    app.listener.forgot_password:
        tags:
            - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest }
```
