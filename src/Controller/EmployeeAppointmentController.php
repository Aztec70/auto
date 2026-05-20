<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Report;
use App\Entity\User;
use App\Form\ReportType;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EmployeeAppointmentController extends AbstractController
{
    #[Route('/employee/appointments', name: 'app_employee_appointments')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getService()) {
            return $this->render('appointment/employee/index.html.twig', [
                'appointments' => [],
                'missingService' => true,
                'selectedStatus' => 'Aktyvios',
                'selectedDate' => '',
            ]);
        }

        $selectedStatus = $request->query->get('status', 'Aktyvios');
        $selectedDate = $request->query->get('date', '');

        $allowedFilters = [
            'Aktyvios',
            'Laukia',
            'Reikia patikslinimo',
            'Patvirtinta',
            'Baigta',
            'Atšaukta',
            'Visos',
        ];

        if (!in_array($selectedStatus, $allowedFilters, true)) {
            $selectedStatus = 'Aktyvios';
        }

        $qb = $entityManager->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->andWhere('a.service = :service')
            ->setParameter('service', $user->getService())
            ->orderBy('a.visitDate', 'ASC');

        if ($selectedStatus === 'Aktyvios') {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', [
                    'Laukia',
                    'Reikia patikslinimo',
                    'Patvirtinta',
                ]);
        } elseif ($selectedStatus !== 'Visos') {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $selectedStatus);
        }

        if ($selectedDate !== '') {
            try {
                $dateFrom = new \DateTime($selectedDate . ' 00:00:00');
                $dateTo = new \DateTime($selectedDate . ' 23:59:59');

                $qb->andWhere('a.visitDate BETWEEN :dateFrom AND :dateTo')
                    ->setParameter('dateFrom', $dateFrom)
                    ->setParameter('dateTo', $dateTo);
            } catch (\Exception) {
                $selectedDate = '';
            }
        }

        $appointments = $qb->getQuery()->getResult();

        return $this->render('appointment/employee/index.html.twig', [
            'appointments' => $appointments,
            'missingService' => false,
            'selectedStatus' => $selectedStatus,
            'selectedDate' => $selectedDate,
        ]);
    }

    #[Route('/employee/appointments/{id}/status/{status}', name: 'app_employee_appointment_status')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateStatus(
        Appointment $appointment,
        string $status,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite keisti šios registracijos.');
        }

        $allowedStatuses = ['Laukia', 'Reikia patikslinimo', 'Patvirtinta', 'Baigta', 'Atšaukta'];

        if (!in_array($status, $allowedStatuses, true)) {
            throw $this->createNotFoundException('Neteisingas statusas.');
        }

        if ($status === 'Reikia patikslinimo') {
            $this->addFlash('error', 'Statusui „Reikia patikslinimo“ naudokite patikslinimo langą.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if ($status === 'Baigta') {
            if (!$appointment->getReport()) {
                if ($appointment->getStatus() !== 'Patvirtinta') {
                    $this->addFlash('error', 'Registraciją galima užbaigti tik tada, kai ji yra patvirtinta.');
                    return $this->redirectToRoute('app_employee_appointments');
                }

                return $this->redirectToRoute('app_employee_report_new', [
                    'id' => $appointment->getId(),
                ]);
            }
        }

        if ($status === 'Patvirtinta') {
            $appointment->setEmployeeNote(null);
            $appointment->setUserClarificationNote(null);
            $appointment->setNeedsDescriptionClarification(false);
            $appointment->setNeedsTimeClarification(false);
            $appointment->setCancelReason(null);
        }

        if ($status === 'Laukia') {
            $appointment->setCancelReason(null);
        }

        $appointment->setStatus($status);
        $appointment->setIsSeen(false);

        $entityManager->flush();

        $this->addFlash('success', 'Registracijos statusas atnaujintas.');

        return $this->redirectToRoute('app_employee_appointments');
    }

    #[Route('/employee/appointments/{id}/note', name: 'app_employee_appointment_note', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateNote(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite redaguoti šios registracijos patikslinimo.');
        }

        if (in_array($appointment->getStatus(), ['Baigta', 'Atšaukta'], true)) {
            $this->addFlash('error', 'Baigtai arba atšauktai registracijai patikslinimo pateikti nebegalima.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $note = trim((string) $request->request->get('employeeNote', ''));
        $needsDescriptionClarification = $request->request->getBoolean('needsDescriptionClarification');
        $needsTimeClarification = $request->request->getBoolean('needsTimeClarification');

        if (!$needsDescriptionClarification && !$needsTimeClarification) {
            $this->addFlash('error', 'Pasirinkite bent vieną patikslinimo tipą.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if ($note === '') {
            $this->addFlash('error', 'Įrašykite pastabą klientui.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if (mb_strlen($note) < 5) {
            $this->addFlash('error', 'Pastaba turi būti bent 5 simbolių ilgio.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if (mb_strlen($note) > 1000) {
            $this->addFlash('error', 'Pastaba negali būti ilgesnė nei 1000 simbolių.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $appointment->setEmployeeNote($note);
        $appointment->setUserClarificationNote(null);
        $appointment->setNeedsDescriptionClarification($needsDescriptionClarification);
        $appointment->setNeedsTimeClarification($needsTimeClarification);
        $appointment->setStatus('Reikia patikslinimo');
        $appointment->setIsSeen(false);

        $entityManager->flush();

        $this->addFlash('success', 'Klientui išsiųstas prašymas patikslinti registraciją.');

        return $this->redirectToRoute('app_employee_appointments');
    }

    #[Route('/employee/appointments/{id}/cancel', name: 'app_employee_appointment_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function cancelAppointment(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite atšaukti šios registracijos.');
        }

        if (in_array($appointment->getStatus(), ['Baigta', 'Atšaukta'], true)) {
            $this->addFlash('error', 'Šios registracijos atšaukti nebegalima.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $cancelReason = trim((string) $request->request->get('cancelReason', ''));

        if ($cancelReason === '') {
            $this->addFlash('error', 'Įrašykite registracijos atšaukimo priežastį.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if (mb_strlen($cancelReason) < 5) {
            $this->addFlash('error', 'Atšaukimo priežastis turi būti bent 5 simbolių ilgio.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $appointment->setStatus('Atšaukta');
        $appointment->setCancelReason($cancelReason);
        $appointment->setEmployeeNote(null);
        $appointment->setUserClarificationNote(null);
        $appointment->setNeedsDescriptionClarification(false);
        $appointment->setNeedsTimeClarification(false);
        $appointment->setIsSeen(false);

        $entityManager->flush();

        $this->addFlash('success', 'Registracija atšaukta. Klientas matys atšaukimo priežastį.');

        return $this->redirectToRoute('app_employee_appointments');
    }

    #[Route('/employee/report/new/{id}', name: 'app_employee_report_new')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function newReport(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite kurti ataskaitos šiai registracijai.');
        }

        if ($appointment->getReport()) {
            $this->addFlash('error', 'Šiai registracijai ataskaita jau sukurta.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        if ($appointment->getStatus() !== 'Patvirtinta') {
            $this->addFlash('error', 'Ataskaitą galima kurti tik patvirtintai registracijai.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $report = new Report();
        $report->setAppointment($appointment);

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($report);

            $appointment->setReport($report);
            $appointment->setStatus('Baigta');
            $appointment->setIsSeen(false);

            $entityManager->flush();

            $this->addFlash('success', 'Ataskaita sėkmingai sukurta.');

            return $this->redirectToRoute('app_employee_appointments');
        }

        return $this->render('report/new.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
        ]);
    }

    #[Route('/employee/report/{id}/edit', name: 'app_employee_report_edit')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function editReport(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite redaguoti šios ataskaitos.');
        }

        if (!$appointment->getReport()) {
            $this->addFlash('error', 'Šiai registracijai ataskaita dar nesukurta.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        $report = $appointment->getReport();

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setUpdatedAt(new \DateTimeImmutable());
            $appointment->setIsSeen(false);

            $entityManager->flush();

            $this->addFlash('success', 'Ataskaita sėkmingai atnaujinta.');

            return $this->redirectToRoute('app_employee_report_show', [
                'id' => $appointment->getId(),
            ]);
        }

        return $this->render('report/edit.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
            'report' => $report,
        ]);
    }

    #[Route('/employee/report/{id}', name: 'app_employee_report_show')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function showReport(Appointment $appointment): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite peržiūrėti šios ataskaitos.');
        }

        if (!$appointment->getReport()) {
            $this->addFlash('error', 'Šiai registracijai ataskaita dar nesukurta.');
            return $this->redirectToRoute('app_employee_appointments');
        }

        return $this->render('report/show.html.twig', [
            'appointment' => $appointment,
            'report' => $appointment->getReport(),
        ]);
    }

    #[Route('/employee/report/{id}/pdf', name: 'app_employee_report_pdf')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function downloadEmployeeReportPdf(Appointment $appointment): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Naudotojas nerastas.');
        }

        if (!$user->getService()) {
            throw $this->createAccessDeniedException('Darbuotojui nėra priskirtas servisas.');
        }

        if ($appointment->getService()?->getId() !== $user->getService()?->getId()) {
            throw $this->createAccessDeniedException('Jūs negalite atsisiųsti šios ataskaitos.');
        }

        if (!$appointment->getReport()) {
            $this->addFlash('error', 'Šiai registracijai ataskaita dar nesukurta.');
            return $this->redirectToRoute('app_employee_appointments');
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