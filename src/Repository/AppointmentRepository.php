<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findConflictingAppointment(
        Service $service,
        \DateTimeInterface $visitDate,
        ?int $excludeAppointmentId = null
    ): ?Appointment {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.service = :service')
            ->andWhere('a.visitDate = :visitDate')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('service', $service)
            ->setParameter('visitDate', $visitDate)
            ->setParameter('statuses', [
                'Laukia',
                'Reikia patikslinimo',
                'Patvirtinta',
            ])
            ->setMaxResults(1);

        if ($excludeAppointmentId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeAppointmentId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}