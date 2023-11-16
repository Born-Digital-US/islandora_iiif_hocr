<?php

namespace Drupal\islandora_iiif_hocr\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Plugin\views\ResultRow;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use LDAP\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * IIIF Search Result Annotations style plugin.
 *
 * @ViewsStyle(
 *   id = "islandora_iiif_hocr_iiif_search_result_annotations",
 *   title = @Translation("IIIF Search Result Annotations"),
 *   help = @Translation("IIIF Annotation formatted search results for paged content."),
 *   display_types = {"data"}
 * )
 */
class IiifSearchResultAnnotations extends StylePluginBase {

protected $usesOptions = TRUE;


  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * The allowed formats for this serializer. Default to only JSON.
   *
   * @var array
   */
  protected $formats = ['json'];

  /**
   * Returns an array of format options.
   *
   * @return string[]
   *   An array of the allowed serializer formats. In this case just JSON.
   */
  public function getFormats() {
    return ['json' => 'json'];
  }
    /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->serializer = $serializer;
  }

    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['wrapper_class'] = ['default' => 'item-list'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {


    $json = [];
    $json["@context"] = "http:\/\/iiif.io\/api\/presentation\/2\/context.json";
    $json["startIndex"] = $this->view->getOffset();

    if ($pager = $this->view->getPager()) {
      $total = $pager->getTotalItems();
    }
    else {

    }

    $json["within"]["total"] = $total;
    $json['within']['@type'] = "sc:Layer";

    $json['@type'] = "sc:AnnotationList";
    foreach ($this->view->result as $row) {
      $json['resources'][] = $this->getAnnotationForRow($row);
    }
    return $this->serializer->serialize($json, 'json', ['views_style_plugin' => $this]);
  }

  protected function getAnnotationForRow(ResultRow $row): array {
    /**
     * @var Drupal\search_api\Item\Item
     */
    $item = $row->_item;
    if ($extra = $item->getExtraData('islandora_hocr_highlights')) {
      foreach ($extra as $snippet_field_name) {
        if (!empty($snippet_field_name['snippets'])) {
          foreach ($snippet_field_name['snippets'] as $snippet) {
            if (!empty($snippet['highlights'])) {
              foreach($snippet['highlights'] as $highlight) {
                $x = $highlight;


              }
            }
          }

        }


      }

    }


return [];
  }
}
