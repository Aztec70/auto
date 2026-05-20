<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\User;
use App\Form\AdminUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    #[Route('/users', name: 'app_admin_users')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $role = trim((string) $request->query->get('role', ''));
        $serviceId = trim((string) $request->query->get('service', ''));

        $qb = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.service', 's')
            ->addSelect('s');

        if ($search !== '') {
            $qb->andWhere('u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role === 'ROLE_ADMIN') {
            $qb->andWhere('u.roles LIKE :adminRole')
               ->setParameter('adminRole', '%ROLE_ADMIN%');
        } elseif ($role === 'ROLE_EMPLOYEE') {
            $qb->andWhere('u.roles LIKE :employeeRole')
               ->setParameter('employeeRole', '%ROLE_EMPLOYEE%');
        } elseif ($role === 'ROLE_USER') {
            $qb->andWhere('u.roles NOT LIKE :adminRole')
               ->andWhere('u.roles NOT LIKE :employeeRole')
               ->setParameter('adminRole', '%ROLE_ADMIN%')
               ->setParameter('employeeRole', '%ROLE_EMPLOYEE%');
        }

        if ($serviceId !== '') {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', (int) $serviceId);
        }

        $qb->addOrderBy("
            CASE
                WHEN u.roles LIKE '%ROLE_ADMIN%' THEN 1
                WHEN u.roles LIKE '%ROLE_EMPLOYEE%' THEN 2
                ELSE 3
            END
        ", 'ASC')
        ->addOrderBy('u.email', 'ASC');

        $users = $qb->getQuery()->getResult();

        $services = $entityManager->getRepository(Service::class)->findBy([], [
            'name' => 'ASC',
        ]);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'services' => $services,
            'search' => $search,
            'selectedRole' => $role,
            'selectedService' => $serviceId,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRole = $form->get('roles')->getData();
            $user->setRoles([$selectedRole]);

            if ($selectedRole !== 'ROLE_EMPLOYEE') {
                $user->setService(null);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vartotojas sėkmingai atnaujintas.');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'editedUser' => $user,
        ]);
    }
}