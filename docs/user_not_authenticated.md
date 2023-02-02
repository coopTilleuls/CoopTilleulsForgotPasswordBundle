# Ensure user is not authenticated

When a user requests a new password, or reset it, it shouldn't be authenticated. But this part is part of your own
application.

Create an EventSubscriber and listen to `kernel.request` event:

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
    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 4.3 and inferior, use 'kernel.request' event name
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->get('_route'), 'coop_tilleuls_forgot_password')) {
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
