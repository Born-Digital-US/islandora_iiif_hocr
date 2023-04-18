<?php

namespace Drupal\islandora_iiif_hocr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\search_api\IndexInterface;
use Drupal\views\Views;


/**
 * Configure Islandora iiif hocr settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_iiif_hocr_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_iiif_hocr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('islandora_iiif_hocr.settings');
        $index_options = [];
    foreach($this->getIndexes() as $index_id => $index) {
      $index_options[$index_id] = $index->label();
    }

    if(count($index_options) > 0) {

    }
    $form['solr_hocr_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OCR Highlighting'),
      ];
    $form['solr_hocr_fieldset']['info'] = [
      '#type' => 'item',
      '#markup' => t("Refer to the islandora_mirador documentation for how to set up text search highlighting in Mirador.")
    ];

    if (count($index_options)) {
      $form['solr_hocr_fieldset']['solr_hocr_paged_content_display'] = [
        '#validated' => TRUE,
        '#type' => 'select',
        '#title' => t('Paged Content IIIF Manifest view/display'),
        '#description' => t("Select the view/display being used to generate the IIIF manifest for repository items identified as \"Paged Content\" (having multiple \"Page\" objects as children)."),
        '#options' => $this->getIiifManifestViewsDisplayOptions('paged_content') ?? [],
        '#default_value' => $config->get('solr_hocr_paged_content_display'),
        '#empty_option' => t('-None-'),
        '#empty_value' => "",
      ];
      $form['solr_hocr_fieldset']['solr_hocr_page_display'] = [
        '#validated' => TRUE,
        '#type' => 'select',
        '#title' => t('Single Page IIIF Manifest view/display'),
        '#description' => t("Select the view/display being used to generate the IIIF manifest for repository items identified as a single \"Page\"."),
        '#options' => $this->getIiifManifestViewsDisplayOptions('page') ?? [],
        '#default_value' => $config->get('solr_hocr_page_display'),
        '#empty_option' => t('-None-'),
        '#empty_value' => "",
      ];
      $form['solr_hocr_fieldset']['solr_hocr_index'] = [
        '#type' => 'select',
        '#title' => t('Select the solr index that you are using to hold your ocr highlight content'),
        '#options' => $index_options,
        '#default_value' => $config->get('solr_hocr_index'),
        '#empty_option' => t('-None-'),
        '#empty_value' => "",
        '#ajax' => [
          'callback' => '::solrHocrFieldListCallback',
          'disable-refocus' => FALSE,
          'event' => 'change',
          'wrapper' => 'edit-solr_hocr_field',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Loading solr index field options...'),
          ],
        ],
      ];
      $form['solr_hocr_fieldset']['solr_hocr_field'] = [
        '#validated' => TRUE,
        '#type' => 'select',
        '#title' => t('Select the solr field that indexes your ocr highlight content'),
        '#options' => $config->get('solr_hocr_index') ? $this->hocrFieldOptionsFromIndexId($config->get('solr_hocr_index')) : [],
        '#default_value' => $config->get('solr_hocr_field'),
        '#empty_option' => t('-None-'),
        '#empty_value' => "",
        '#prefix' => '<div id="edit-solr_hocr_field">',
        '#suffix' => '</div>',
        '#states' => [
          'invisible' => [
            ':input[name="solr_hocr_index"]' => ['value' => ''],
          ],
        ],
      ];
    }
    else {
      $form['solr_hocr_fieldset']['no-index'] = [
        '#type' => 'item',
        '#markup' => '<div class="warning">' . t("No solr index found that contains a `Fulltext \"ocr_highlight\"` field.") . '</div>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('islandora_iiif_hocr.settings')
      ->set('solr_hocr_paged_content_display', $form_state->getValue('solr_hocr_paged_content_display'))
      ->set('solr_hocr_page_display', $form_state->getValue('solr_hocr_page_display'))
      ->set('solr_hocr_index', $form_state->getValue('solr_hocr_index'))
      ->set('solr_hocr_field', $form_state->getValue('solr_hocr_field'))
      ->save();
    parent::submitForm($form, $form_state);
  }

    /**
   * Get list of search_api_solr indexes that:
   * 1. Index nodes (datasource id = entity:node)
   * 2. Include the text_ocr field type definition.
   *
   * @return \Drupal\search_api\IndexInterface[]|void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  private function getIndexes($index_id = NULL) {
    $datasource_id = 'entity:media';

    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->loadMultiple(!empty($index_id) ? [$index_id] : NULL);

    foreach ($indexes as $index_id => $index) {
      $dependencies = $index->getServerInstance()->getDependencies();
      if (!$index->isValidDatasource($datasource_id)
        || empty($dependencies['config'])
        || !in_array('search_api_solr.solr_field_type.text_ocr_und_7_0_0', $dependencies['config'])
      ) {
        unset($indexes[$index_id]);
      }

      return $indexes;
    }
  }

    /**
   * Provide form options lists to select view and display that generate iiif manifests for
   * "Paged Content" and "Page" objects.
   *
   * @param  string  $manifestType
   *  'page' or 'paged_content'
   *
   * @return array
   *  An options list of identifiers constructed as `[view id]/[display id]`.
   */
  private function getIiifManifestViewsDisplayOptions(string $manifestType) {
    $options = [];
    $allViews = Views::getAllViews();
    /** @var Drupal\views\Entity\View $aView */
    foreach($allViews as $aView) {
      if($aView->get('base_table') == 'media_field_data') {
        $default_arguments = $aView->getDisplay('default')['display_options']['arguments'] ?? [];
        foreach($aView->get('display') as $displayId => $display) {
          if(!empty($display['display_options']['style']['type']) && $display['display_options']['style']['type'] == 'iiif_manifest') {
            $display = $aView->getDisplay($displayId);
            $arguments = $display['display_options']['arguments'] ?? $default_arguments;
            switch($manifestType) {
              case 'paged_content':
                if(!empty($arguments['field_member_of_target_id']) && $arguments['field_member_of_target_id']['relationship'] == 'field_media_of') {
                  $options[$aView->id() . "/" . $displayId] = $aView->label() . " (" . $aView->id() . ") / " . $display['display_title'] . " (". $displayId . ")";
                }
                break;
              case 'page':
                if(!empty($arguments['field_media_of_target_id']) && (empty($arguments['field_media_of_target_id']['relationship'] == 'none') || $arguments['field_media_of_target_id']['relationship'] == 'none')) {
                  $options[$aView->id() . "/" . $displayId] = $aView->label() . " (" . $aView->id() . ") / " . $display['display_title'] . " (". $displayId . ")";
                }
                break;
            }
          }
        }
      }
    }
    return $options;
  }

  /**
   * @param $index_id
   *
   * Get a list of the media fields that use the ocr_highlight solr field type.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  private function hocrFieldOptionsFromIndexId($index_id) {
    $options = [];
    if(!empty($index_id)) {
      $search_api_index = $this->getIndexes($index_id)[$index_id] ?? NULL;
      if($search_api_index) {
        // Start by loading all the field type configs and getting a list of ocr_highlight field types.
        $configs = \Drupal::service('config.storage')->readMultiple(\Drupal::service('config.storage')->listAll('search_api_solr.solr_field_type'));
        foreach ($configs as $config) {
          if (!empty($config['custom_code']) && strpos($config['custom_code'], 'ocr_highlight') === 0) {
            // Here we end up with an array of search_api solr_text_custom fields and their corresponding language.
            $hocr_solr_field_languages['solr_text_custom:' . $config['custom_code']][] = $config['field_type_language_code'];
          }
        }
        $media_solr_fields = $search_api_index->getFieldsByDatasource('entity:media');
        foreach ($media_solr_fields as $field_id => $field_definition) {
          if (!empty($hocr_solr_field_languages[$field_definition->getType()])) {
            $options[$field_id] = $field_definition->getLabel();
          }
        }
      }
    }
    return $options;
  }

}
