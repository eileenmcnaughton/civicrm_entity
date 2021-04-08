<?php

namespace Drupal\civicrm_entity_leaflet\Plugin\views\style;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\leaflet_views\Controller\LeafletAjaxPopupController;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\leaflet_views\Plugin\views\style\LeafletMap;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "civicrm_entity_address_leaflet_map",
 *   title = @Translation("CiviCRM Entity Address Leaflet Map"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class CivicrmEntityAddressLeafletMap extends LeafletMap {

  /**
   * Get a list of fields and a sublist of geo data fields in this view.
   *
   * @return array
   *   Available data sources.
   */
  protected function getAvailableDataSources() {
    $fields_geo_data = [];

    /* @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler) */
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $this->viewFields[$field_id] = $label;
      if (is_a($handler, '\Drupal\views\Plugin\views\field\EntityField')) {
        $fields_geo_data[$field_id] = $label;
      }
    }

    return $fields_geo_data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    // If data source changed then apply the changes.
    if ($form_state->get('entity_source')) {
      $this->options['entity_source'] = $form_state->get('entity_source');
      $this->entityInfo = $this->getEntitySourceEntityInfo($this->options['entity_source']);
      $this->entityType = $this->entityInfo->id();
      $this->entitySource = $this->options['entity_source'];
    }

    StylePluginBase::buildOptionsForm($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#attached'] = [
      'library' => [
        'leaflet/general',
      ],
    ];

    // Get a sublist of geo data fields in the view.
    $fields_geo_data = $this->getAvailableDataSources();

    $wrapper_id = 'leaflet-map-views-style-options-form-wrapper';
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    // Map preset.
    $form['data_source_lat'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Source Latitude'),
      '#description' => $this->t('Which field contains the latitude?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source_lat'],
      '#required' => TRUE,
    ];

    $form['data_source_lon'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Source Longitude'),
      '#description' => $this->t('Which field contains longitude?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source_lon'],
      '#required' => TRUE,
    ];

    // Get the possible entity sources.
    $entity_sources = $this->getAvailableEntitySources();

    // If there is only one entity source it will be the base entity, so don't
    // show the element to avoid confusing people.
    if (count($entity_sources) == 1) {
      $form['entity_source'] = [
        '#type' => 'value',
        '#value' => key($entity_sources),
      ];
    }
    else {
      $form['entity_source'] = [
        '#type' => 'select',
        '#title' => new TranslatableMarkup('Entity Source'),
        '#description' => new TranslatableMarkup('Select which Entity should be used as Leaflet Mapping base Entity.<br><u>Leave as "View Base Entity" to rely on default Views behaviour, and don\'t specifically needed otherwise</u>.'),
        '#options' => $entity_sources,
        '#default_value' => !empty($this->options['entity_source']) ? $this->options['entity_source'] : '__base_table',
        '#ajax' => [
          'wrapper' => $wrapper_id,
          'callback' => [static::class, 'optionsFormEntitySourceSubmitAjax'],
          'trigger_as' => ['name' => 'entity_source_submit'],
        ],
      ];
      $form['entity_source_submit'] = [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Update Entity Source'),
        '#name' => 'entity_source_submit',
        '#submit' => [
          [static::class, 'optionsFormEntitySourceSubmit'],
        ],
        '#validate' => [],
        '#limit_validation_errors' => [
          ['style_options', 'entity_source'],
        ],
        '#attributes' => [
          'class' => ['js-hide'],
        ],
        '#ajax' => [
          'wrapper' => $wrapper_id,
          'callback' => [static::class, 'optionsFormEntitySourceSubmitAjax'],
        ],
      ];
    }

    // Name field.
    $form['name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title Field'),
      '#description' => $this->t('Choose the field which will appear as a title on tooltips.'),
      '#options' => array_merge(['' => ' - None - '], $this->viewFields),
      '#default_value' => $this->options['name_field'],
    ];

    $desc_options = array_merge(['' => ' - None - '], $this->viewFields);
    // Add an option to render the entire entity using a view mode.
    if ($this->entityType) {
      $desc_options += [
        '#rendered_entity' => $this->t('< @entity entity >', ['@entity' => $this->entityType]),
        '#rendered_entity_ajax' => $this->t('< @entity entity via ajax >', ['@entity' => $this->entityType]),
        '#rendered_view_fields' => $this->t('# Rendered View Fields (with field label, format, classes, etc)'),
      ];
    }

    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description Field'),
      '#description' => $this->t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#required' => FALSE,
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
    ];

    if ($this->entityType) {

      // Get the human readable labels for the entity view modes.
      $view_mode_options = [];
      foreach ($this->entityDisplay->getViewModes($this->entityType) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $form['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View mode the entity will be displayed in the Infowindow.'),
        '#options' => $view_mode_options,
        '#default_value' => $this->options['view_mode'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[description_field]"]' => [
              ['value' => '#rendered_entity'],
              ['value' => '#rendered_entity_ajax'],
            ],
          ],
        ],
      ];
    }

    // Generate the Leaflet Map General Settings.
    $this->generateMapGeneralSettings($form, $this->options);

    // Generate the Leaflet Map Reset Control.
    $this->setResetMapControl($form, $this->options);

    // Generate the Leaflet Map Position Form Element.
    $map_position_options = $this->options['map_position'];
    $form['map_position'] = $this->generateMapPositionElement($map_position_options);

    // Generate the Leaflet Map weight/zIndex Form Element.
    $form['weight'] = $this->generateWeightElement($this->options['weight']);

    // Generate Icon form element.
    $icon_options = $this->options['icon'];
    $form['icon'] = $this->generateIconFormElement($icon_options);

    // Set Map Marker Cluster Element.
    $this->setMapMarkerclusterElement($form, $this->options);

    // Set Map Geometries Options Element.
    $this->setMapPathOptionsElement($form, $this->options);

    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    $this->setGeocoderMapControl($form, $this->options);
  }

  /**
   * Renders the View.
   */
  public function render() {
    $data = [];

    // Collect bubbleable metadata when doing early rendering.
    $build_for_bubbleable_metadata = [];

    // Always render the map, otherwise ...
    $leaflet_map_style = !isset($this->options['leaflet_map']) ? $this->options['map'] : $this->options['leaflet_map'];
    $map = leaflet_map_get_info($leaflet_map_style);

    // Set Map additional map Settings.
    $this->setAdditionalMapOptions($map, $this->options);

    // Add a specific map id.
    $map['id'] = Html::getUniqueId("leaflet_map_view_" . $this->view->id() . '_' . $this->view->current_display);

    if (($lat_field_name = $this->options['data_source_lat']) && ($lon_field_name = $this->options['data_source_lon'])) {
      $this->renderFields($this->view->result);

      /* @var \Drupal\views\ResultRow $result */
      foreach ($this->view->result as $id => $result) {

        $lat_value = (array) $this->getFieldValue($result->index, $lat_field_name);
        $long_value = (array) $this->getFieldValue($result->index, $lon_field_name);

        if (!empty($lat_value) && !empty($long_value)) {

          $features = [[
            'type' => 'point',
            'lat' => $lat_value[0],
            'lon' => $long_value[0],
          ]];

          if (!empty($result->_entity)) {
            // Entity API provides a plain entity object.
            $entity = $result->_entity;
          }
          elseif (isset($result->_object)) {
            // Search API provides a TypedData EntityAdapter.
            $entity_adapter = $result->_object;
            if ($entity_adapter instanceof EntityAdapter) {
              $entity = $entity_adapter->getValue();
            }
          }

          // Render the entity with the selected view mode.
          if (isset($entity)) {
            // Get and set (if not set) the Geofield cardinality.
            /* @var \Drupal\Core\Field\FieldItemList $geofield_entity */
            if (!isset($map['geofield_cardinality'])) {
              try {
                $geofield_entity = $entity->get($geofield_name);
                $map['geofield_cardinality'] = $geofield_entity->getFieldDefinition()
                  ->getFieldStorageDefinition()
                  ->getCardinality();
              }
              catch (\Exception $e) {
                // In case of exception it means that $geofield_name field is
                // not directly related to the $entity and might be the case of
                // a geofield exposed through a relationship.
                // In this case it is too complicate to get the geofield related
                // entity, so apply a more general case of multiple/infinite
                // geofield_cardinality.
                // @see: https://www.drupal.org/project/leaflet/issues/3048089
                $map['geofield_cardinality'] = -1;
              }
            }

            $entity_type = $entity->getEntityTypeId();
            $entity_type_langcode_attribute = $entity_type . '_field_data_langcode';

            $view = $this->view;

            // Set the langcode to be used for rendering the entity.
            $rendering_language = $view->display_handler->getOption('rendering_language');
            $dynamic_renderers = [
              '***LANGUAGE_entity_translation***' => 'TranslationLanguageRenderer',
              '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
            ];
            if (isset($dynamic_renderers[$rendering_language])) {
              /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
              $langcode = isset($result->$entity_type_langcode_attribute) ? $result->$entity_type_langcode_attribute : $entity->language()
                ->getId();
            }
            else {
              if (strpos($rendering_language, '***LANGUAGE_') !== FALSE) {
                $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
              }
              else {
                // Specific langcode set.
                $langcode = $rendering_language;
              }
            }

            switch ($this->options['description_field']) {
              case '#rendered_entity':
                $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())
                  ->view($entity, $this->options['view_mode'], $langcode);
                $render_context = new RenderContext();
                $description = $this->renderer->executeInRenderContext($render_context, function () use (&$build) {
                  return $this->renderer->render($build, TRUE);
                });
                if (!$render_context->isEmpty()) {
                  $render_context->update($build_for_bubbleable_metadata);
                }
                break;

              case '#rendered_entity_ajax':
                $parameters = [
                  'entity_type' => $entity_type,
                  'entity' => $entity->id(),
                  'view_mode' => $this->options['view_mode'],
                  'langcode' => $langcode,
                ];
                $url = Url::fromRoute('leaflet_views.ajax_popup', $parameters);
                $description = sprintf('<div class="leaflet-ajax-popup" data-leaflet-ajax-popup="%s" %s></div>',
                  $url->toString(), LeafletAjaxPopupController::getPopupIdentifierAttribute($entity_type, $entity->id(), $this->options['view_mode'], $langcode));
                $map['settings']['ajaxPoup'] = TRUE;
                break;

              case '#rendered_view_fields':
                // Normal rendering via view/row fields (with labels options,
                // formatters, classes, etc.).
                $render_row = [
                  "markup" => $this->view->rowPlugin->render($result),
                ];
                $description = !empty($this->options['description_field']) ? $this->renderer->renderPlain($render_row) : '';
                break;

              default:
                // Row rendering of single specified field value (without
                // labels).
                $description = !empty($this->options['description_field']) ? $this->rendered_fields[$result->index][$this->options['description_field']] : '';
            }

            // Merge eventual map icon definition from hook_leaflet_map_info.
            if (!empty($map['icon'])) {
              $this->options['icon'] = $this->options['icon'] ?: [];

              // Remove empty icon options so that they might be replaced by
              // the ones set by the hook_leaflet_map_info.
              foreach ($this->options['icon'] as $k => $icon_option) {
                if (empty($icon_option) || (is_array($icon_option) && $this->leafletService->multipleEmpty($icon_option))) {
                  unset($this->options['icon'][$k]);
                }
              }
              $this->options['icon'] = array_replace($map['icon'], $this->options['icon']);
            }

            // Define possible tokens.
            $tokens = [];
            foreach ($this->rendered_fields[$result->index] as $field_name => $field_value) {
              $tokens[$field_name] = $field_value;
              $tokens["{{ $field_name }}"] = $field_value;
            }

            $icon_type = isset($this->options['icon']['iconType']) ? $this->options['icon']['iconType'] : 'marker';

            // Relates the feature with additional properties.
            foreach ($features as &$feature) {

              // Attach pop-ups if we have a description field.
              // Add its entity id, so that it might be referenced from outside.
              $feature['entity_id'] = $entity->id();

              // Generate the weight feature property
              // (falls back to natural result ordering).
              $feature['weight'] = !empty($this->options['weight']) ? intval(str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['weight'], $tokens))) : $id;

              // Attach pop-ups if we have a description field.
              if (isset($description)) {
                $feature['popup'] = $description;
              }
              // Attach also titles, they might be used later on.
              if ($this->options['name_field']) {
                // Decode any entities because JS will encode them again and
                // we don't want double encoding.
                $feature['label'] = !empty($this->options['name_field']) ? Html::decodeEntities(($this->rendered_fields[$result->index][$this->options['name_field']])) : '';
              }

              // Eventually set the custom Marker icon (DivIcon, Icon Url or
              // Circle Marker).
              if ($feature['type'] === 'point' && isset($this->options['icon'])) {
                // Set Feature Icon properties.
                $feature['icon'] = $this->options['icon'];

                // Transforms Icon Options that support Replacement
                // Patterns/Tokens.
                if (!empty($this->options["icon"]["iconSize"]["x"])) {
                  $feature['icon']["iconSize"]["x"] = $this->viewsTokenReplace($this->options["icon"]["iconSize"]["x"], $tokens);
                }
                if (!empty($this->options["icon"]["iconSize"]["y"])) {
                  $feature['icon']["iconSize"]["y"] = $this->viewsTokenReplace($this->options["icon"]["iconSize"]["y"], $tokens);
                }
                if (!empty($this->options["icon"]["shadowSize"]["x"])) {
                  $feature['icon']["shadowSize"]["x"] = $this->viewsTokenReplace($this->options["icon"]["shadowSize"]["x"], $tokens);
                }
                if (!empty($this->options["icon"]["shadowSize"]["y"])) {
                  $feature['icon']["shadowSize"]["y"] = $this->viewsTokenReplace($this->options["icon"]["shadowSize"]["y"], $tokens);
                }

                switch ($icon_type) {
                  case 'html':
                    $feature['icon']['html'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['html'], $tokens));
                    $feature['icon']['html_class'] = $this->options['icon']['html_class'];
                    break;

                  case 'circle_marker':
                    $feature['icon']['options'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['circle_marker_options'], $tokens));
                    break;

                  default:
                    if (!empty($this->options['icon']['iconUrl'])) {
                      $feature['icon']['iconUrl'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['iconUrl'], $tokens));
                      // Generate correct Absolute iconUrl & shadowUrl,
                      // if not external.
                      if (!empty($feature['icon']['iconUrl'])) {
                        $feature['icon']['iconUrl'] = $this->leafletService->pathToAbsolute($feature['icon']['iconUrl']);
                      }
                    }
                    if (!empty($this->options['icon']['shadowUrl'])) {
                      $feature['icon']['shadowUrl'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['shadowUrl'], $tokens));
                      if (!empty($feature['icon']['shadowUrl'])) {
                        $feature['icon']['shadowUrl'] = $this->leafletService->pathToAbsolute($feature['icon']['shadowUrl']);
                      }
                    }

                    // Set the Feature IconSize and ShadowSize to the IconUrl or
                    // ShadowUrl Image sizes (if empty or invalid).
                    $this->leafletService->setFeatureIconSizesIfEmptyOrInvalid($feature);

                    break;
                }
              }

              // Associate dynamic path properties (token based) to each
              // feature, in case of not point.
              if ($feature['type'] !== 'point') {
                $feature['path'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['path'], $tokens));
              }

              // Associate dynamic className property (token based) to icon.
              $feature['icon']['className'] = !empty($this->options['icon']['className']) ? str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['className'], $tokens)) : '';

              // Allow modules to adjust the marker.
              $this->moduleHandler->alter('leaflet_views_feature', $feature, $result, $this->view->rowPlugin);
            }

            // Add new points to the whole basket.
            $data = array_merge($data, $features);
          }
        }
      }
    }

    // Don't render the map, if we do not have any data
    // and the hide option is set.
    if (empty($data) && !empty($this->options['hide_empty_map'])) {
      return [];
    }

    // Order the data features based on the 'weight' element.
    uasort($data, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    $js_settings = [
      'map' => $map,
      'features' => $data,
    ];

    // Allow other modules to add/alter the map js settings.
    $this->moduleHandler->alter('leaflet_map_view_style', $js_settings, $this);

    $map_height = !empty($this->options['height']) ? $this->options['height'] . $this->options['height_unit'] : '';
    $element = $this->leafletService->leafletRenderMap($js_settings['map'], $js_settings['features'], $map_height);
    // Add the Core Drupal Ajax library for Ajax Popups.
    if (isset($map['settings']['ajaxPoup']) && $map['settings']['ajaxPoup'] == TRUE) {
      $build_for_bubbleable_metadata['#attached']['library'][] = 'core/drupal.ajax';
    }
    BubbleableMetadata::createFromRenderArray($element)
      ->merge(BubbleableMetadata::createFromRenderArray($build_for_bubbleable_metadata))
      ->applyTo($element);
    return $element;
  }

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['data_source_lat'] = ['default' => ''];
    $options['data_source_lon'] = ['default' => ''];
    $options['entity_source'] = ['default' => '__base_table'];
    $options['name_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['view_mode'] = ['default' => 'full'];

    $leaflet_map_default_settings = [];
    foreach (self::getDefaultSettings() as $k => $setting) {
      $leaflet_map_default_settings[$k] = ['default' => $setting];
    }
    return $options + $leaflet_map_default_settings;
  }

}
