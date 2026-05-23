<?php

declare(strict_types=1);

namespace App\Http\AdminController;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\SourceErrorRepository;
use App\Repository\SourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    #[Route('', name: 'app.admin.index')]
    public function index(
        SourceRepository $sourceRepository,
        CategoryRepository $categoryRepository,
        ArticleRepository $articleRepository,
        SourceErrorRepository $sourceErrorRepository,
    ): Response {
        return $this->render('admin/index.html.twig', [
            'sources' => $sourceRepository->count(),
            'categories' => $categoryRepository->count(),
            'feeds' => $articleRepository->count(),
            'errors' => $sourceErrorRepository->count(),
            'mostActiveSources30Days' => $sourceRepository->findMostActive(30, 10),
            'mostActiveSources7Days' => $sourceRepository->findMostActive(7, 10),
            'mostActiveCategories30Days' => $categoryRepository->findMostActive(30, 10),
            'mostActiveCategories7Days' => $categoryRepository->findMostActive(7, 10),
        ]);
    }
}
