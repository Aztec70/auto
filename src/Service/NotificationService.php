<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getEmployeePendingCount(User $user): int
    {
        if (!$user->getService()) {
            return 0;
        }

        return $this->em->getRepository(Appointment::class)->count([
            'service' => $user->getService(),
            'status' => 'Laukia',
        ]);
    }

    public function getUserNotificationCount(User $user): int
    {
        return $this->em->getRepository(Appointment::class)->count([
            'user' => $user,
            'isSeen' => false,
        ]);
    }
}