<?php

/**
 * @file
 * Includes FSA theme node preprocess functionality.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Implements template_preprocess_node().
 */
function fsa_preprocess_node(&$variables) {

  // Set vars for easy access.
  $node = $variables['node'];

  if ($variables['teaser']) {
    // Override title if it is set on the respective field.
    if (isset($node->field_liftup_title->value) && $node->field_liftup_title->value != '') {
      $variables['label'] = $node->field_liftup_title->value;
    }

    // Replace teaser body value with summary (even if no summary set).
    if (isset($variables['content']['body']['#items'])) {
      $variables['content']['body'][0]['#text'] = $variables['content']['body']['#items']->getValue()[0]['summary'];
    }
  }

  // Set content type tag for the search result template.
  if ($variables['view_mode'] == 'search_result') {
    // Use bundle name as default (to have at least something when switch below
    // does not set the value.
    $tag = $node->bundle();

    switch ($node->getType()) {
      case 'page':
        // Use content type taxonomy.
        $tid = ($node->hasField('field_content_type')) ? $node->get('field_content_type')->target_id : 0;
        if (is_numeric($tid) && $term = Term::load($tid)) {
          $tag = $term->label();
        }
        break;

      case 'alert':
        if ($node->hasField('field_alert_type')) {
          $tag = $node->field_alert_type->view([
            'label' => 'hidden',
            'type' => 'fsa_alert_type_formatter',
          ]);
        }
        break;

      case 'consultation':
        if ($node->hasField('field_consultations_type')) {
          $tag = $node->field_consultations_type->view([
            'label' => 'hidden',
            'settings' => [
              'link' => FALSE,
            ],
          ]);
        }
        break;

      case 'news':
        $tag = t('News');
        break;

      case 'research_project':
        $tag = t('Research project');
        break;

    }
    $variables['tag'] = $tag;
  }
}
