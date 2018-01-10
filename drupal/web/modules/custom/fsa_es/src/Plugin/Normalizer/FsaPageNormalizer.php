<?php

namespace Drupal\fsa_es\Plugin\Normalizer;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Normalizes Drupal entities into an array structure good for ES.
 */
class FsaPageNormalizer extends NormalizerBase {

  use StringTranslationTrait;

  /** @var array $taxonomyTreeCache */
  protected $taxonomyTreeCache = [];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['\Drupal\node\Entity\Node'];

  /**
   * Supported formats.
   *
   * @var array
   */
  protected $format = ['elasticsearch_helper'];

  /** @var \Drupal\Core\Datetime\DateFormatter $dateFormatter */
  protected $dateFormatter;

  /**
   * FsaPageNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   */
  public function __construct(EntityManagerInterface $entity_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_a($data, '\Drupal\node\Entity\Node') && $data->bundle() == 'page';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\node\NodeInterface $object
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // Get audience term tree indexed by term ID.
    $audience_term_tree = $this->getTaxonomyTree('audience');

    // Store either date updated or date changed.
    $date_updated = $object->get('field_update_date')->value;
    $date_changed = $this->dateFormatter->format($object->get('changed')->value, 'custom', DATETIME_DATETIME_STORAGE_FORMAT, DATETIME_STORAGE_TIMEZONE);

    $data = [
      'name' => $object->label(),
      'body' => implode(' ', [
        $this->prepareTextualField($object->get('field_intro')->getString()),
        $this->prepareTextualField($object->get('body')->getString()),
      ]),
      'content_type' => array_map(function($item) {
        return [
          'id' => $item->id(),
          'label' => $item->label(),
        ];
      }, $object->get('field_content_type')->referencedEntities()),
      'audience' => array_map(function($item) use ($audience_term_tree) {
        $f1 = 1;
        return [
          'id' => $item->id(),
          'depth' => isset($audience_term_tree[$item->id()]->depth) ? $audience_term_tree[$item->id()]->depth : 0,
          'label' => $item->label(),
        ];
      }, $object->get('field_audience')->referencedEntities()),
      'nation' => array_map(function($item) {
        return [
          'id' => $item->id(),
          'label' => $item->label(),
        ];
      }, $object->get('field_nation')->referencedEntities()),
      'updated' => $date_updated ? $date_updated : $date_changed,
    ];

    return $data;
  }

  /**
   * Returns a taxonomy tree indexed by term IDs.
   *
   * @param $vid
   *
   * @return object[]
   */
  protected function getTaxonomyTree($vid) {
    if (!isset($this->taxonomyTreeCache[$vid])) {
      if ($tree = $this->entityManager->getStorage('taxonomy_term')->loadTree($vid)) {
        // Store terms keyed by term ID.
        $indexed_tree = [];

        foreach ($tree as $term) {
          $indexed_tree[$term->tid] = $term;
        }

        $this->taxonomyTreeCache[$vid] = $indexed_tree;
      }
      else {
        // Store empty result in cache.
        $this->taxonomyTreeCache[$vid] = NULL;
      }
    }

    return !is_null($this->taxonomyTreeCache[$vid]) ? $this->taxonomyTreeCache[$vid] : [];
  }

}