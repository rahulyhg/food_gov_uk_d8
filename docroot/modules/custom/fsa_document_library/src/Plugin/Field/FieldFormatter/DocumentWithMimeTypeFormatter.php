<?php

namespace Drupal\fsa_document_library\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Plugin implementation to style a document download link.
 *
 * @FieldFormatter(
 *   id = "fsa_document_mime_type_formatter",
 *   label = @Translation("Detailed download link"),
 *   field_types = {
 *      "file"
 *   }
 * )
 */
class DocumentWithMimeTypeFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $url = NULL;
    $files = $this->getEntitiesToView($items, $langcode);

    // Loop through the file entities.
    foreach ($files as $delta => $file) {

      $file_uri = $file->getFileUri();
      $url = Url::fromUri(file_create_url($file_uri));

      if ($items[0]->description != '') {
        $link_text = $items[0]->description;
      }
      else {
        $link_text = $file->getFilename();
      }

      $filesize = format_size($file->getSize());

      $link = Link::fromTextAndUrl($link_text, $url)->toString();

      $attributes['class'] = 'file__type_' . Html::cleanCssIdentifier($file->getMimeType());

      $elements[$delta] = [
        '#theme' => 'fsa_file_download',
        '#attributes' => $attributes,
        '#filename' => $file->getFilename(),
        '#url' => $url,
        '#link' => $link,
        '#mimetype' => $file->getMimeType(),
        '#filesize' => $filesize,
      ];
    }

    return $elements;
  }

}
