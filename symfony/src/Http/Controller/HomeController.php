<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app.home')]
    public function index(
        CategoryRepository $categoryRepository,
        ArticleRepository $articleRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[ValueResolver('query')] string $query,
        #[ValueResolver('pageSize')] int $pageSize,
        #[MapQueryParameter] ?int $category = null,
    ): Response {
        $selectedCategory = null;
        if (null !== $category) {
            $selectedCategory = $categoryRepository->find($category);
        }

        $feeds = $paginator->paginate(
            $articleRepository->findByCategoryQuery($selectedCategory, $query),
            $request->query->getInt('page', 1),
            $pageSize
        );

        return $this->render('index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'selectedCategory' => $selectedCategory,
            'feeds' => $feeds,
        ]);
    }
}
