<?php

declare(strict_types=1);

namespace App\Http\AdminController;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/category', name: 'app.admin.category.')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[ValueResolver('query')] string $query,
        #[ValueResolver('pageSize')] int $pageSize,
    ): Response {
        $reportory = $categoryRepository->createQueryBuilder('c')
            ->orderBy('c.id')
            ->getQuery();

        if (!empty($query)) {
            $reportory = $categoryRepository->findByQuery($query);
        }

        $categories = $paginator->paginate(
            $reportory,
            $request->query->getInt('page', 1),
            $pageSize
        );

        $form = $this->createForm(CategoryType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Category $category */
            $category = $form->getData();
            $categoryRepository->save($category, true);

            return $this->redirectToRoute('app.admin.category.index');
        }

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
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

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        CategoryRepository $categoryRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), (string) $request->getPayload()->get('_token'))) {
            foreach ($category->getSources()->toArray() as $source) {
                $category->removeSource($source);
            }
            $categoryRepository->remove($category, true);
        }

        return $this->redirectToRoute('app.admin.category.index');
    }
}
