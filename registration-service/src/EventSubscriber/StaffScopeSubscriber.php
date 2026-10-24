<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\StaffUser;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Applies the signed-in staff member's scope to the database session and
 * ends idle sessions.
 *
 * Mission staff get app.scope = 'mission' and app.mission = their mission
 * code, so PostgreSQL row-level security returns only that mission's records
 * even if application code forgets a filter. Account administrators get
 * 'accounts', which returns no records at all. HQ roles get 'hq'. Sessions end after 15 minutes
 * without activity.
 */
final class StaffScopeSubscriber implements EventSubscriberInterface
{
    public const IDLE_SECONDS = 900;
    private const LAST_SEEN = 'staff_last_seen';

    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // After the firewall (priority 8) has loaded the user.
        return [
            KernelEvents::REQUEST => ['onRequest', 4],
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$event->isMainRequest() || !$user instanceof StaffUser) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $lastSeen = (int) $session->get(self::LAST_SEEN, time());
        if (time() - $lastSeen > self::IDLE_SECONDS) {
            $this->security->logout(false);
            $session->getFlashBag()->add('notice', 'You were signed out after 15 minutes without activity.');
            $event->setResponse(new RedirectResponse($this->urls->generate('staff_login')));

            return;
        }
        $session->set(self::LAST_SEEN, time());

        $mission = $user->getRole() === StaffUser::ROLE_MISSION_STAFF ? $user->getMission()?->getCode() : null;
        if ($user->getRole() === StaffUser::ROLE_MISSION_STAFF && $mission === null) {
            throw new \LogicException('Mission staff account without a mission.');
        }
        $this->connection->executeQuery(
            "SELECT set_config('app.scope', ?, false), set_config('app.mission', ?, false)",
            [match ($user->getRole()) {
                StaffUser::ROLE_MISSION_STAFF => 'mission',
                StaffUser::ROLE_ACCOUNT_ADMIN => 'accounts',
                default => 'hq',
            }, $mission ?? ''],
        );
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if ($this->connection->isConnected()) {
            $this->connection->executeQuery("SELECT set_config('app.scope', '', false), set_config('app.mission', '', false)");
        }
    }
}
