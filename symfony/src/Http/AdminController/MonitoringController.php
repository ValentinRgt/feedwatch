<?php

declare(strict_types=1);

namespace App\Http\AdminController;

use App\Entity\SourceError;
use App\Repository\SourceErrorRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/monitoring', name: 'app.admin.monitoring.')]
final class MonitoringController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        SourceErrorRepository $sourceErrorRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[ValueResolver('pageSize')] int $pageSize,
    ): Response {
        $query = $sourceErrorRepository
            ->createQueryBuilder('e')
            ->innerJoin('e.source', 's')
            ->addSelect('s')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery();

        $errors = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $pageSize,
        );

        return $this->render('admin/monitoring/index.html.twig', [
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        SourceError $sourceError,
        SourceErrorRepository $sourceErrorRepository,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $sourceError->getId(), (string) $request->getPayload()->get('_token'))) {
            $sourceErrorRepository->remove($sourceError, true);
        }

        return $this->redirectToRoute('app.admin.monitoring.index');
    }
}
