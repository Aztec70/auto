<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\CategoryRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class ServiceController extends AbstractController
{
    #[Route('/service/new', name: 'service_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/services',
                        $newFilename
                    );

                    $service->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Nepavyko įkelti serviso nuotraukos.');

                    return $this->render('service/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Servisas sėkmingai sukurtas.');

            return $this->redirectToRoute('service_list');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/services', name: 'service_list')]
    public function list(
        Request $request,
        ServiceRepository $repository,
        CategoryRepository $categoryRepository
    ): Response {
        $categoryId = trim((string) $request->query->get('category', ''));
        $serviceId = trim((string) $request->query->get('service', ''));

        $categories = $categoryRepository->findBy([], [
            'name' => 'ASC',
        ]);

        $allServices = $repository->findBy([], [
            'name' => 'ASC',
        ]);

        $qb = $repository->createQueryBuilder('s')
            ->leftJoin('s.categories', 'c')
            ->addSelect('c')
            ->distinct()
            ->orderBy('s.name', 'ASC');

        if ($categoryId !== '') {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', (int) $categoryId);
        }

        if ($serviceId !== '') {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', (int) $serviceId);
        }

        $services = $qb->getQuery()->getResult();

        return $this->render('service/list.html.twig', [
            'services' => $services,
            'allServices' => $allServices,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'selectedService' => $serviceId,
        ]);
    }

    #[Route('/service/{id}/edit', name: 'service_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Service $service,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ServiceType::class, $service);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $oldImageName = $service->getImageName();

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/services',
                        $newFilename
                    );

                    $service->setImageName($newFilename);

                    if ($oldImageName) {
                        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/services/' . $oldImageName;

                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Nepavyko atnaujinti serviso nuotraukos.');

                    return $this->render('service/edit.html.twig', [
                        'form' => $form->createView(),
                        'service' => $service,
                    ]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Servisas sėkmingai atnaujintas.');

            return $this->redirectToRoute('service_list');
        }

        return $this->render('service/edit.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
        ]);
    }

    #[Route('/service/{id}/delete', name: 'service_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Service $service, EntityManagerInterface $entityManager): Response
    {
        $imageName = $service->getImageName();

        if ($imageName) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/services/' . $imageName;

            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $entityManager->remove($service);
        $entityManager->flush();

        $this->addFlash('success', 'Servisas sėkmingai ištrintas.');

        return $this->redirectToRoute('service_list');
    }
}