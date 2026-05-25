<?php

declare(strict_types=1);

namespace App\Http\AdminController;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/article', name: 'app.admin.article.')]
final class ArticleController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        ArticleRepository $articleRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[ValueResolver('query')] string $query,
        #[ValueResolver('pageSize')] int $pageSize,
    ): Response {
        $reportory = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery();

        if (!empty($query)) {
            $reportory = $articleRepository->findByQuery($query);
        }

        $articles = $paginator->paginate(
            $reportory,
            $request->query->getInt('page', 1),
            $pageSize
        );

        return $this->render('admin/article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Article $article,
        ArticleRepository $articleRepository,
        TranslatorInterface $translator,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), (string) $request->getPayload()->get('_token'))) {
            $articleRepository->remove($article, true);
            $this->addFlash('success', $translator->trans('pages.admin.articles.index.delete.success'));
        }

        return $this->redirectToRoute('app.admin.article.index');
    }
}
