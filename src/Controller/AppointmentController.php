<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AppointmentController extends AbstractController
{
    #[Route('/appointments/new', name: 'app_appointment_new')]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AppointmentRepository $appointmentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $appointment = new Appointment();
        $appointment->setUser($user);

        $preselectedCategory = null;
        $serviceId = $request->query->get('service');

        if ($serviceId) {
            $service = $entityManager->getRepository(Service::class)->find($serviceId);

            if ($service) {
                $appointment->setService($service);

                if (!$service->getCategories()->isEmpty()) {
                    $preselectedCategory = $service->getCategories()->first();
                }
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment);

        if ($preselectedCategory) {
            $form->get('category')->setData($preselectedCategory);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service = $appointment->getService();
            $visitDate = $appointment->getVisitDate();

            if ($service instanceof Service) {
                if (
                    $service->getWorkDayFrom() === null ||
                    $service->getWorkDayTo() === null ||
                    $service->getWorkTimeFrom() === null ||
                    $service->getWorkTimeTo() === null
                ) {
                    $form->get('visitDate')->addError(
                        new FormError('Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.')
                    );

                    return $this->render('appointment/user/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            if ($service && $visitDate) {
                $conflictingAppointment = $appointmentRepository->findConflictingAppointment($service, $visitDate);

                if ($conflictingAppointment) {
                    $form->get('visitDate')->addError(
                        new FormError('Šis laikas jau užimtas pasirinktame servise.')
                    );

                    return $this->render('appointment/user/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->persist($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Registracija sėkmingai sukurta.');

            return $this->redirectToRoute('app_appointment_index');
        }

        return $this->render('appointment/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/appointments', name: 'app_appointment_index')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $appointments = $entityManager->getRepository(Appointment::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $hasUnseenAppointments = false;

        foreach ($appointments as $appointment) {
            if ($appointment->isSeen() === false) {
                $appointment->setIsSeen(true);
                $hasUnseenAppointments = true;
            }
        }

        if ($hasUnseenAppointments) {
            $entityManager->flush();
        }

        return $this->render('appointment/user/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/appointments/busy-slots', name: 'app_appointment_busy_slots', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function busySlots(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $serviceId = $request->query->getInt('serviceId');
        $excludeId = $request->query->get('excludeId');

        if (!$serviceId) {
            return $this->json([
                'slots' => [],
            ]);
        }

        $qb = $entityManager->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->where('IDENTITY(a.service) = :serviceId')
            ->andWhere('a.visitDate >= :today')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('serviceId', $serviceId)
            ->setParameter('today', new \DateTime('today', new \DateTimeZone('Europe/Vilnius')))
            ->setParameter('statuses', [
                'Laukia',
                'Reikia patikslinimo',
                'Patvirtinta',
            ])
            ->orderBy('a.visitDate', 'ASC');

        if ($excludeId) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', (int) $excludeId);
        }

        $appointments = $qb->getQuery()->getResult();

        $slots = [];

        foreach ($appointments as $appointment) {
            if ($appointment instanceof Appointment && $appointment->getVisitDate()) {
                $slots[] = $appointment->getVisitDate()->format('Y-m-d H:i');
            }
        }

        return $this->json([
            'slots' => $slots,
        ]);
    }

    #[Route('/appointments/{id}/edit', name: 'app_appointment_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager,
        AppointmentRepository $appointmentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($appointment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite redaguoti šios registracijos.');
        }

        if (!in_array($appointment->getStatus(), ['Laukia', 'Reikia patikslinimo'], true)) {
            $this->addFlash('error', 'Galima redaguoti tik laukiančią arba patikslinimo reikalaujančią registraciją.');

            return $this->redirectToRoute('app_appointment_index');
        }

        $form = $this->createForm(AppointmentType::class, $appointment);

        $service = $appointment->getService();
        if ($service && !$service->getCategories()->isEmpty()) {
            $form->get('category')->setData($service->getCategories()->first());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service = $appointment->getService();
            $visitDate = $appointment->getVisitDate();

            if ($service instanceof Service) {
                if (
                    $service->getWorkDayFrom() === null ||
                    $service->getWorkDayTo() === null ||
                    $service->getWorkTimeFrom() === null ||
                    $service->getWorkTimeTo() === null
                ) {
                    $form->get('visitDate')->addError(
                        new FormError('Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.')
                    );

                    return $this->render('appointment/user/edit.html.twig', [
                        'form' => $form->createView(),
                        'appointment' => $appointment,
                    ]);
                }
            }

            if ($service && $visitDate) {
                $conflictingAppointment = $appointmentRepository->findConflictingAppointment(
                    $service,
                    $visitDate,
                    $appointment->getId()
                );

                if ($conflictingAppointment) {
                    $form->get('visitDate')->addError(
                        new FormError('Šis laikas jau užimtas pasirinktame servise.')
                    );

                    return $this->render('appointment/user/edit.html.twig', [
                        'form' => $form->createView(),
                        'appointment' => $appointment,
                    ]);
                }
            }

            $appointment->setStatus('Laukia');
            $appointment->setEmployeeNote(null);
            $appointment->setUserClarificationNote(null);
            $appointment->setNeedsDescriptionClarification(false);
            $appointment->setNeedsTimeClarification(false);

            $entityManager->flush();

            $this->addFlash('success', 'Registracija sėkmingai atnaujinta.');

            return $this->redirectToRoute('app_appointment_index');
        }

        return $this->render('appointment/user/edit.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
        ]);
    }

    #[Route('/appointments/{id}/clarify', name: 'app_appointment_clarify', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function clarify(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager,
        AppointmentRepository $appointmentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($appointment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite patikslinti šios registracijos.');
        }

        if ($appointment->getStatus() !== 'Reikia patikslinimo') {
            $this->addFlash('error', 'Šiai registracijai patikslinimo nereikia.');
            return $this->redirectToRoute('app_appointment_index');
        }

        $changes = [];

        if ($appointment->isNeedsDescriptionClarification()) {
            $problemDescription = trim((string) $request->request->get('problemDescription', ''));

            if ($problemDescription === '') {
                $this->addFlash('error', 'Įrašykite patikslintą problemos aprašymą.');
                return $this->redirectToRoute('app_appointment_index');
            }

            if (mb_strlen($problemDescription) < 5) {
                $this->addFlash('error', 'Problemos aprašymas turi būti bent 5 simbolių ilgio.');
                return $this->redirectToRoute('app_appointment_index');
            }

            $appointment->setProblemDescription($problemDescription);
            $changes[] = 'patikslino problemos aprašymą';
        }

        if ($appointment->isNeedsTimeClarification()) {
            $newVisitDateValue = trim((string) $request->request->get('visitDate', ''));

            if ($newVisitDateValue === '') {
                $this->addFlash('error', 'Pasirinkite naują vizito laiką.');
                return $this->redirectToRoute('app_appointment_index');
            }

            try {
                $newVisitDate = new \DateTime($newVisitDateValue);
            } catch (\Exception) {
                $this->addFlash('error', 'Neteisingas datos formatas.');
                return $this->redirectToRoute('app_appointment_index');
            }

            if ($newVisitDate < new \DateTime()) {
                $this->addFlash('error', 'Negalima pasirinkti praėjusio laiko.');
                return $this->redirectToRoute('app_appointment_index');
            }

            $service = $appointment->getService();

            if ($service instanceof Service) {
                if (
                    $service->getWorkDayFrom() === null ||
                    $service->getWorkDayTo() === null ||
                    $service->getWorkTimeFrom() === null ||
                    $service->getWorkTimeTo() === null
                ) {
                    $this->addFlash('error', 'Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.');
                    return $this->redirectToRoute('app_appointment_index');
                }
            }

            if ($service && $newVisitDate) {
                $conflictingAppointment = $appointmentRepository->findConflictingAppointment(
                    $service,
                    $newVisitDate,
                    $appointment->getId()
                );

                if ($conflictingAppointment) {
                    $this->addFlash('error', 'Šis laikas jau užimtas pasirinktame servise.');
                    return $this->redirectToRoute('app_appointment_index');
                }
            }

            $appointment->setVisitDate($newVisitDate);
            $changes[] = 'pakeitė vizito laiką';
        }

        if (empty($changes)) {
            $this->addFlash('error', 'Nėra ką patikslinti.');
            return $this->redirectToRoute('app_appointment_index');
        }

            $appointment->setStatus('Laukia');
            $appointment->setEmployeeNote(null);
            $appointment->setNeedsDescriptionClarification(false);
            $appointment->setNeedsTimeClarification(false);
            $appointment->setUserClarificationNote('Klientas ' . implode(' ir ', $changes) . '.');
            $appointment->setIsSeen(false);

        $entityManager->flush();

        $this->addFlash('success', 'Registracija sėkmingai patikslinta.');

        return $this->redirectToRoute('app_appointment_index');
    }

    #[Route('/appointments/{id}/delete', name: 'app_appointment_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($appointment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite ištrinti šios registracijos.');
        }

        if ($appointment->getStatus() !== 'Laukia') {
            $this->addFlash('error', 'Galima atšaukti tik laukiančią registraciją.');

            return $this->redirectToRoute('app_appointment_index');
        }

        $entityManager->remove($appointment);
        $entityManager->flush();

        $this->addFlash('success', 'Registracija sėkmingai atšaukta.');

        return $this->redirectToRoute('app_appointment_index');
    }

    #[Route('/my/report/{id}', name: 'app_user_report_show')]
    #[IsGranted('ROLE_USER')]
    public function showUserReport(Appointment $appointment): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if ($appointment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Negalite peržiūrėti šios ataskaitos.');
        }

        if (!$appointment->getReport()) {
            $this->addFlash('error', 'Ataskaita dar nesukurta.');
            return $this->redirectToRoute('app_appointment_index');
        }

        return $this->render('report/show.html.twig', [
            'appointment' => $appointment,
            'report' => $appointment->getReport(),
        ]);
    }

    #[Route('/my/report/{id}/pdf', name: 'app_user_report_pdf')]
    #[IsGranted('ROLE_USER')]
    public function downloadUserReportPdf(Appointment $appointment): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if ($appointment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Negalite atsisiųsti šios ataskaitos.');
        }

        if (!$appointment->getReport()) {
            $this->addFlash('error', 'Ataskaita dar nesukurta.');
            return $this->redirectToRoute('app_appointment_index');
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->renderView('report/pdf.html.twig', [
            'appointment' => $appointment,
            'report' => $appointment->getReport(),
        ]);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'ataskaita-' . $appointment->getId() . '.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}