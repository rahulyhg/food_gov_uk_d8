<?php

namespace Drupal\fsa_ratings\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for the ratings helper.
 *
 * @package Drupal\fsa_ratings\Controller
 */
class RatingsHelper extends ControllerBase {

  /**
   * Build a ratings badge.
   *
   * @param string $rating
   *   The rating value.
   * @param string $image_size
   *   Image size (small|medium|large)
   * @param bool $value_only
   *   True to parse the badge from value only.
   *
   * @return string
   *   Rating image badge (@todo: use drupal image functionality)
   */
  public static function ratingBadge($rating, $image_size = 'medium', $value_only = TRUE) {

    // Get language to be used in badge.
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if ($value_only) {
      // If value is numeric use FHIS scheme badge.
      $scheme = (is_numeric($rating)) ? 'fhrs' : 'fhis';

      if ($scheme == 'fhis') {
        $filter = [
          ' ' => '_',
          '/' => '_',
          '[' => '_',
          ']' => '_',
        ];
        $rating = Html::cleanCssIdentifier($rating, $filter);
      }
      $ratingkey = $scheme . '_' . $rating . '_' . $lang . '-gb';
    }
    else {
      // If full rating badge name passed.
      $ratingkey = $rating;
    }
    $alt = t('Food hygiene Rating score @score', ['@score' => $rating]);
    // @todo: Store the rating badge images locally instead of pulling from FSA.
    return '<div class="badge ratingkey"><img src="http://ratings.food.gov.uk/images/scores/' . $image_size . '/' . $ratingkey . '.JPG" alt="' . $alt .'" /></div>';
  }

  /**
   * Unified form cache setting.
   */
  public static function formCacheControl() {
    // Disables form cache to make the form usable for anonymous.
    // @todo: is this really the best way?
    return ['max-age' => 0];
  }

}
