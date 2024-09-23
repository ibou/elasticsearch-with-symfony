<?php

namespace App\Controller;

use App\Form\Model\SearchModel;
use App\Form\Type\SearchFormType;
use DateTimeImmutable;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchPhrase;
use Elastica\Query\MatchQuery;
use Elastica\Query\Range;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{

  public function __construct(
    private PaginatorInterface $paginator,
    private PaginatedFinderInterface $finder
  ) {}

  #[Route('/search', name: 'search')]
  public function search(Request $request): Response
  {

    $form = $this->createForm(SearchFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var SearchModel $data */
      $data = $form->getData();
      $page = $request->query->getInt('page', 1);
      $boolQuery = new BoolQuery();

      if ($data->query) {
        $matchQuery = new MatchPhrase('title', $data->query);
        $boolQuery->addMust($matchQuery);
      }

      if ($data->category) {
        $matchQuery = new MatchQuery('category.id', $data->category->getId());
        $boolQuery->addMust($matchQuery);
      }

      if ($data->createdThisMonth) {
        $rangeQuery = new Range('createdAt', ['gte' => (new \DateTimeImmutable('-1 month'))->format('Y-m-d')]);
        $boolQuery->addFilter($rangeQuery);
      }

      $results = $this->finder->createPaginatorAdapter($boolQuery);
      $pagination = $this->paginator->paginate($results, $page, 50);
    }


    return $this->render('search/index.html.twig', [
      'form' => $form->createView(),
      'results' => [],
      'pagination' => $pagination ?? null,
    ]);
  }
}
