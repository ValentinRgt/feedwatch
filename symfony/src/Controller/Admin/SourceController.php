<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Source;
use App\Entity\Category;
use App\Form\SourceType;
use App\Form\CategoryType;
use App\Repository\SourceRepository;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/source', name: 'app.admin.source.')]
final class SourceController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        SourceRepository $sourceRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $sources = $paginator->paginate(
            $sourceRepository->createQueryBuilder('s')->orderBy('s.id')->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        $form = $this->createForm(SourceType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Source $source */
            $source = $form->getData();
            $sourceRepository->save($source, true);

            return $this->redirectToRoute('app.admin.source.index');
        }

        return $this->render('admin/source/index.html.twig', [
            'sources' => $sources,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Category $category,
        CategoryRepository $categoryRepository,
        Request $request,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Category $category */
            $category = $form->getData();
            $categoryRepository->save($category, true);

            $this->addFlash('success', $translator->trans('pages.admin.categories.edit.success'));

            return $this->redirectToRoute('app.admin.category.index');
        }

        return $this->render('admin/source/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        CategoryRepository $categoryRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), (string) $request->getPayload()->get('_token'))) {
            $categoryRepository->remove($category, true);
        }

        return $this->redirectToRoute('app.admin.category.index');
    }
}
