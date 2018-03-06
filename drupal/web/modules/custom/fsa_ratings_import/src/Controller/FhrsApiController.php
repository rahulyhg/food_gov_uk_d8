<?php

namespace Drupal\fsa_ratings_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class FhrsApiController.
 *
 * @package Drupal\fhrs_api\Controller
 */
class FhrsApiController extends ControllerBase {

  /**
   * FHRS API base URL.
   *
   * @return string
   *   The API base url.
   */
  protected function baseUrl() {
    // @todo: Move URL to configs
    // @todo: USING STAGING UNTIL API UPDATES THE PRODUCTION.
    $url = 'http://staging-api.ratings.food.gov.uk/';
    return $url;
  }

  /**
   * Add FHRS API required headers.
   *
   * @return array
   *   Headers
   */
  protected function headers() {
    $headers = [
      'headers' => [
        'accept' => 'application/json',
        'x-api-version' => 2,
      ],
    ];
    return $headers;
  }

  /**
   * Get status code of FHRS API.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|int
   *   Status code or error.
   */
  public static function status() {

    $client = new Client();
    try {
      $res = $client->get(FhrsApiController::baseUrl(), ['http_errors' => FALSE]);
      return($res->getStatusCode());
    }
    catch (RequestException $e) {
      return t('Status check failed');
    }
  }

  /**
   * Get total count of FHRS items.
   *
   * @param array $filters
   *   Array of filters to build a querystring.
   *
   * @return int
   *   Total count
   */
  public static function totalCount(array $filters = []) {

    // Get only one item from API to get the meta (reduces the response time).
    $filters['pageSize'] = 1;

    // Take filters and build a query for the API.
    $query = UrlHelper::buildQuery($filters);

    $url = FhrsApiController::baseUrl() . 'Establishments?' . $query;

    // Add FHRS required headers.
    $headers = FhrsApiController::headers();

    $client = \Drupal::httpClient();
    try {
      $res = $client->get($url, $headers);
      $count = Json::decode($res->getBody());
      $count = $count['meta']['totalCount'];
      return $count;
    }
    catch (RequestException $e) {
      \Drupal::logger('fsa_ratings_import')->error('Failed getting totalcount from the API: ' . $e);
      return FALSE;
    }
  }

  /**
   * Build array of single-establishment only API calls.
   *
   * @param string $since
   *   Updated since string date value.
   *
   * @return array|bool
   *   Array of urls.
   */
  public static function getUrlForItemsToUpdate($since = '-3 day') {
    $urls = [];
    $query = UrlHelper::buildQuery(['updatedSince' => date("Y-m-d", strtotime($since))]);
    $url = FhrsApiController::baseUrl() . 'Establishments/basic?' . $query;

    // Add FHRS required headers.
    $headers = FhrsApiController::headers();

    $client = \Drupal::httpClient();
    try {
      $res = $client->get($url, $headers);
      $body = Json::decode($res->getBody());

      $establishments = $body['establishmentsExtended'];
      foreach ($establishments as $establishment) {
        $urls[] = FhrsApiController::baseUrl() . 'Establishments/' . $establishment['FHRSID'];
      }

      return $urls;

    }
    catch (RequestException $e) {
      \Drupal::logger('fsa_ratings_import')->error('Failed to request for updated establishment items: ' . $e);
      return FALSE;
    }
  }

}
