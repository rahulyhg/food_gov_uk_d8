<?php

namespace Drupal\fsa_ratings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\fsa_es\SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for the ratings search.
 *
 * @package Drupal\fsa_ratings\Controller
 */
class RatingsSearch extends ControllerBase {

  /** @var SearchService  */
  private $searchService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fsa_es.search_service')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function __construct(SearchService $searchService) {
    $this->searchService = $searchService;
  }


  /**
   * Page callback for /ratings/search
   *
   */
  public function ratingsSearch() {
    $items = [];
    $categories = [];
    $hits = 0;
    $available_filters = $this->searchService->categories();

    // User provided search input
    $keywords = \Drupal::request()->query->get('q');

    // User provided max item count. Hard-limit is 1000. Default is 20.
    $max_items = \Drupal::request()->query->get('max');
    if (empty($max_items) || $max_items > 1000) {
      $max_items = 20;
    }

    $filters = [];
    // See if the following parameters are provided by the user and add to the list of filters
    $filter_param_names = ['local_authority', 'business_type', 'rating_value'];
    foreach ($filter_param_names as $opt) {
      $value = \Drupal::request()->query->get($opt);
      if (!empty($value)) {
        $filters[$opt] = $value;
      }
    }

    // Ask results from the service only when either the filter values or keywords are given
    if (!empty($keywords) || !empty($filters)) {
      // Execute the search using the SearchService. Maximum returned item count is
      $results = $this->searchService->search($keywords, $filters, $max_items);
      $hits = $results['total'];

      foreach ($results['results'] as $result) {
        // Add the link to the entity v:iew page
        $result['url'] = Url::fromRoute('entity.fsa_establishment.canonical', ['fsa_establishment' => $result['id']]);
        $items[] = [
          '#theme' => 'fsa_ratings_search_result_item',
          '#item' => $result,
        ];
      }
    }

    return [
      '#theme' => 'fsa_ratings_search_page',
      '#form' => \Drupal::formBuilder()->getForm('Drupal\fsa_ratings\Form\FsaRatingsSearchForm'),
      '#items' => $items,                         // Actual result items
      '#categories' => $categories,               // Aggregation results, list of categories of the result items
      '#keywords' => $keywords,                   // Keywords given in the URL
      '#available_filters' => $available_filters, // Meaningful filters (which have content associated)
      '#applied_filters' => $filters,             // Filters given by the user and used for the querying
      '#hits_total' => $hits,                     // Total count of the results
      '#hits_shown' => count($items),             // Item count to be shown now
    ];
  }

}
