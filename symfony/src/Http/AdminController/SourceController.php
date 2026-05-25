<?php

declare(strict_types=1);

namespace App\Http\AdminController;

use App\Entity\Source;
use App\Form\SourceType;
use App\Repository\SourceRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/source', name: 'app.admin.source.')]
final class SourceController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        SourceRepository $sourceRepository,
        PaginatorInterface $paginator,
        Request $request,
        #[ValueResolver('query')] string $query,
        #[ValueResolver('pageSize')] int $pageSize,
    ): Response {
        $reportory = $sourceRepository->createQueryBuilder('s')
            ->orderBy('s.id')
            ->getQuery();

        if (!empty($query)) {
            $reportory = $sourceRepository->findByQuery($query);
        }

        $sources = $paginator->paginate(
            $reportory,
            $request->query->getInt('page', 1),
            $pageSize
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
        Source $source,
        SourceRepository $sourceRepository,
        Request $request,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(SourceType::class, $source);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Source $source */
            $source = $form->getData();
            $source->setChecksum(null);
            $source->setLastFetchedAt(null);
            $sourceRepository->save($source, true);

            $this->addFlash('success', $translator->trans('pages.admin.sources.edit.success'));

            return $this->redirectToRoute('app.admin.source.index');
        }

        return $this->render('admin/source/edit.html.twig', [
            'source' => $source,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Source $source,
        SourceRepository $sourceRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $source->getId(), (string) $request->getPayload()->get('_token'))) {
            $sourceRepository->remove($source, true);
        }

        return $this->redirectToRoute('app.admin.source.index');
    }
}
