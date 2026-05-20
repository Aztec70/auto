<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private Security $security,
        private NotificationService $notificationService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('employee_pending_count', [$this, 'getEmployeePendingCount']),
            new TwigFunction('user_notification_count', [$this, 'getUserNotificationCount']),
        ];
    }

    public function getEmployeePendingCount(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        if (!in_array('ROLE_EMPLOYEE', $user->getRoles(), true)) {
            return 0;
        }

        return $this->notificationService->getEmployeePendingCount($user);
    }

    public function getUserNotificationCount(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        if (!in_array('ROLE_USER', $user->getRoles(), true)) {
            return 0;
        }

        return $this->notificationService->getUserNotificationCount($user);
    }
}