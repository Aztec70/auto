<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminCategoryController extends AbstractController
{
    #[Route('', name: 'app_admin_categories')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findBy([], [
            'name' => 'ASC',
        ]);

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_admin_category_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingCategory = $entityManager->getRepository(Category::class)->findOneBy([
                'name' => $category->getName(),
            ]);

            if ($existingCategory) {
                $this->addFlash('error', 'Tokia kategorija jau egzistuoja.');

                return $this->render('admin/category/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Kategorija sėkmingai sukurta.');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_category_edit')]
    public function edit(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingCategory = $entityManager->getRepository(Category::class)->findOneBy([
                'name' => $category->getName(),
            ]);

            if ($existingCategory && $existingCategory->getId() !== $category->getId()) {
                $this->addFlash('error', 'Tokia kategorija jau egzistuoja.');

                return $this->render('admin/category/edit.html.twig', [
                    'form' => $form->createView(),
                    'category' => $category,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Kategorija sėkmingai atnaujinta.');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): Response
    {
        if (!$category->getServices()->isEmpty()) {
            $this->addFlash('error', 'Negalima ištrinti kategorijos, nes ji priskirta vienam ar keliems servisams.');

            return $this->redirectToRoute('app_admin_categories');
        }

        $entityManager->remove($category);
        $entityManager->flush();

        $this->addFlash('success', 'Kategorija sėkmingai ištrinta.');

        return $this->redirectToRoute('app_admin_categories');
    }
}