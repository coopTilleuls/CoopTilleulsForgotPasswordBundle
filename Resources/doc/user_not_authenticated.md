Ensure user is not authenticated
--------------------------------

When a user requests a new password, or reset it, user shouldn't be authenticated. But this part is part of your own
application.

Create an EventListener and listen to `kernel.request` event:

```php
// src/EventSubscriber/ForgotPasswordEventSubscriber.php
namespace App\EventSubscriber;

// ...
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            // Symfony 4.3 and inferior, use 'kernel.request' event name
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

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
