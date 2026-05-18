<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app.home')]
    public function index(
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] ?int $category = null,
    ): Response {
        $selectedCategory = null;
        if (null !== $category) {
            $selectedCategory = $categoryRepository->find($category);
        }

        return $this->render('index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'selectedCategory' => $selectedCategory,
            'feeds' => []
        ]);
    }
}
