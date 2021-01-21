<?php

namespace Drupal\civicrm_entity_leaflet\Plugin\views\style;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\leaflet_views\Controller\LeafletAjaxPopupController;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Leaflet\LeafletService;
use Drupal\Component\Utility\Html;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\leaflet\LeafletSettingsElementsTrait;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Views;

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
class CivicrmEntityAddressLeafletMap extends StylePluginBase implements ContainerFactoryPluginInterface {

  use LeafletSettingsElementsTrait;

  /**
   * The Default Settings.
   *
   * @var array
   */
  protected $defaultSettings;

  /**
   * The Entity source property.
   *
   * @var string
   */
  protected $entitySource;

  /**
   * The Entity type property.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The Entity Info service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityInfo;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Entity Field manager service property.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Display Repository service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The list of fields added to the view.
   *
   * @var array
   */
  protected $viewFields = [];

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a LeafletMap style instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display
   *   The entity display manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display,
    AccountInterface $current_user,
    MessengerInterface $messenger,
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler,
    LeafletService $leaflet_service,
    LinkGeneratorInterface $link_generator,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->defaultSettings = self::getDefaultSettings();
    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplay = $entity_display;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->leafletService = $leaflet_service;
    $this->link = $link_generator;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('leaflet.service'),
      $container->get('link_generator'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // We want to allow view editors to select which entity out of a
    // possible set they want to use to pass to the MapThemer plugin. Long term
    // it would probably be better not to pass an entity to MapThemer plugin and
    // instead pass the result row.
    if (!empty($options['entity_source']) && $options['entity_source'] != '__base_table') {
      $handler = $this->displayHandler->getHandler('relationship', $options['entity_source']);
      $this->entitySource = $options['entity_source'];

      $data = Views::viewsData();
      if (($table = $data->get($handler->definition['base'])) && !empty($table['table']['entity type'])) {
        try {
          $this->entityInfo = $this->entityManager->getDefinition($table['table']['entity type']);
          $this->entityType = $this->entityInfo->id();
        }
        catch (\Exception $e) {
          watchdog_exception('geofield_map', $e);
        }
      }
    }
    else {
      $this->entitySource = '__base_table';

      // For later use, set entity info related to the View's base table.
      $base_tables = array_keys($view->getBaseTables());
      $base_table = reset($base_tables);
      if ($this->entityInfo = $view->getBaseEntityType()) {
        $this->entityType = $this->entityInfo->id();
        return;
      }

      // Eventually try to set entity type & info from base table suffix
      // (i.e. Search API views).
      if (!isset($this->entityType)) {
        $index_id = substr($base_table, 17);
        $index = Index::load($index_id);
        foreach ($index->getDatasources() as $datasource) {
          if ($datasource instanceof DatasourceInterface) {
            $this->entityType = $datasource->getEntityTypeId();
            try {
              $this->entityInfo = $this->entityManager->getDefinition($this->entityType);
            }
            catch (\Exception $e) {
              watchdog_exception('leaflet', $e);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue($index, $field) {
    $this->view->row_index = $index;
    $value = isset($this->view->field[$field]) ? $this->view->field[$field]->getValue($this->view->result[$index]) : NULL;
    unset($this->view->row_index);
    return $value;
  }

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
        /* @var \Drupal\views\Plugin\views\field\EntityField $handler */
        try {
          $entity_type = $handler->getEntityType();
        }
        catch (\Exception $e) {
          $entity_type = NULL;
        }
        $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
        $field_storage_definition = $field_storage_definitions[$handler->definition['field_name']];

        $type = $field_storage_definition->getType();
        $definition = $this->fieldTypeManager->getDefinition($type);
        //if (is_a($definition['class'], '\Drupal\geofield\Plugin\Field\FieldType\GeofieldItem', TRUE)) {
          $fields_geo_data[$field_id] = $label;
        //}
      }
    }

    return $fields_geo_data;
  }

  /**
   * Get options for the available entity sources.
   *
   * Entity source controls which entity gets passed to the MapThemer plugin. If
   * not set it will always default to the view base entity.
   *
   * @return array
   *   The entity sources list.
   */
  protected function getAvailableEntitySources() {
    if ($base_entity_type = $this->view->getBaseEntityType()) {
      $label = $base_entity_type->getLabel();
    }
    else {
      // Fallback to the base table key.
      $base_tables = array_keys($this->view->getBaseTables());
      // A view without a base table should never happen (just in case).
      $label = $base_tables[0] ?? $this->t('Unknown');
    }

    $options = [
      '__base_table' => new TranslatableMarkup('View Base Entity (@entity_type)', [
        '@entity_type' => $label,
      ]),
    ];

    $data = Views::viewsData();
    /** @var \Drupal\views\Plugin\views\HandlerBase $handler */
    foreach ($this->displayHandler->getHandlers('relationship') as $relationship_id => $handler) {
      if (($table = $data->get($handler->definition['base'])) && !empty($table['table']['entity type'])) {
        try {
          $entity_type = $this->entityManager->getDefinition($table['table']['entity type']);
        }
        catch (\Exception $e) {
          $entity_type = NULL;
        }
        $options[$relationship_id] = new TranslatableMarkup('@relationship (@entity_type)', [
          '@relationship' => $handler->adminLabel(),
          '@entity_type' => $entity_type->getLabel(),
        ]);
      }
    }

    return $options;
  }

  /**
   * Get the entity info of the entity source.
   *
   * @param string $source
   *   The Source identifier.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type.
   */
  protected function getEntitySourceEntityInfo($source) {
    if (!empty($source) && ($source != '__base_table')) {
      $handler = $this->displayHandler->getHandler('relationship', $source);

      $data = Views::viewsData();
      if (($table = $data->get($handler->definition['base'])) && !empty($table['table']['entity type'])) {
        try {
          return $this->entityManager->getDefinition($table['table']['entity type']);
        }
        catch (\Exception $e) {
          $entity_type = NULL;
        }
      }
    }

    return $this->view->getBaseEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    // Render map even if there is no data.
    return TRUE;
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

    parent::buildOptionsForm($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#attached'] = [
      'library' => [
        'leaflet/general',
      ],
    ];

    // Get a sublist of geo data fields in the view.
    $fields_geo_data = $this->getAvailableDataSources();

    // Check whether we have a geo data field we can work with.
    /*
    if (!count($fields_geo_data)) {
      $form['error'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Please add at least one Geofield to the View and come back here to set it as Data Source.'),
        '#attributes' => [
          'class' => ['leaflet-warning'],
        ],
      ];
      return;
    }*/

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

    // Generate Icon form element.
    $icon_options = $this->options['icon'];
    $form['icon'] = $this->generateIconFormElement($icon_options, $form);

    // Add Replacement pattern
    // code from LeafletSettingsElementsTrait.php
    if (method_exists($this, 'getProvider') && $this->getProvider() == 'civicrm_entity_leaflet') {
      $twig_link = $this->link->generate('Twig', Url::fromUri('http://twig.sensiolabs.org/documentation', [
        'absolute' => TRUE,
        'attributes' => ['target' => 'blank'],
      ])
      );

      $icon_url_description .= '<br>' . $this->t('You may include @twig_link. You may enter data from this view as per the "Replacement patterns" below.', [
        '@twig_link' => $twig_link,
      ]);

      $form['icon']['iconUrl']['#description'] .= $icon_url_description;
      $form['icon']['shadowUrl']['#description'] .= $icon_url_description;

      // Setup the tokens for views fields.
      // Code is snatched from Drupal\views\Plugin\views\field\FieldPluginBase.
      $options = [];
      $optgroup_fields = (string) t('Fields');
      if (isset($this->displayHandler)) {
        foreach ($this->displayHandler->getHandlers('field') as $id => $field) {
          /* @var \Drupal\views\Plugin\views\field\EntityField $field */
          $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($field->label(), ":"), 2);
        }
      }

      // Default text.
      $output = [];
      // We have some options, so make a list.
      if (!empty($options)) {
        $output[] = [
          '#markup' => '<p>' . $this->t("The following replacement tokens are available. Fields may be marked as <em>Exclude from display</em> if you prefer.") . '</p>',
        ];
        foreach (array_keys($options) as $type) {
          if (!empty($options[$type])) {
            $items = [];
            foreach ($options[$type] as $key => $value) {
              $items[] = $key;
            }
            $item_list = [
              '#theme' => 'item_list',
              '#items' => $items,
            ];
            $output[] = $item_list;
          }
        }
      }

      # Locate where insert available patterns
      $key_idx=0;
      foreach ($form['icon'] as $key => $value){
        # just below 'shadowUrl'
        if ($key == 'shadowUrl') {
          $key_idx++;
          break;
        } 
        else {
          $key_idx++;
        }
      }
      
      if ($key_idx > 0) {
        # if key found, insert help below
        $array_tmp = array_slice($form['icon'], 0, $key_idx);
        $array_tmp['help'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#value' => $output,
        ];
        $array_tmp=array_merge($array_tmp, array_slice($form['icon'], $key_idx));
        $form['icon']=$array_tmp;
        unset($array_tmp);
      }
      else {
        # insert at the end form
        $form['icon']['help'] = [
          '#type' => 'details',
          '#title' => $this->t('Replacement patterns'),
          '#value' => $output,
        ];
      }
    }
    
    // Set Map Marker Cluster Element.
    $this->setMapMarkerclusterElement($form, $this->options);

    // Set Map Geometries Options Element.
    $this->setMapPathOptionsElement($form, $this->options);

    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    $this->setGeocoderMapControl($form, $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $style_options = $form_state->getValue('style_options');
    if (!empty($style_options['height']) && (!is_numeric($style_options['height']) || $style_options['height'] <= 0)) {
      $form_state->setError($form['height'], $this->t('Map height needs to be a positive number.'));
    }
    $icon_options = isset($style_options['icon']) ? $style_options['icon'] : [];
    if (!empty($icon_options['iconSize']['x']) && (!is_numeric($icon_options['iconSize']['x']) || $icon_options['iconSize']['x'] <= 0)) {
      $form_state->setError($form['icon']['iconSize']['x'], $this->t('Icon width needs to be a positive number.'));
    }
    if (!empty($icon_options['iconSize']['y']) && (!is_numeric($icon_options['iconSize']['y']) || $icon_options['iconSize']['y'] <= 0)) {
      $form_state->setError($form['icon']['iconSize']['y'], $this->t('Icon height needs to be a positive number.'));
    }
  }

  /**
   * Submit to update the data source.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form state.
   */
  public static function optionsFormEntitySourceSubmit(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    array_push($parents, 'entity_source');

    // Set the data source selected in the form state and rebuild the form.
    $form_state->set('entity_source', $form_state->getValue($parents));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback to reload the options form after data source change.
   *
   * This allows the entityType (which can be affected by which source
   * is selected to alter the form.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form state.
   *
   * @return mixed
   *   The returned result.
   */
  public static function optionsFormEntitySourceSubmitAjax(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = $triggering_element['#array_parents'];
    array_pop($array_parents);

    return NestedArray::getValue($form, $array_parents);
  }

  /**
   * Renders the View.
   */
  public function render() {
    // Performs some preprocess on the leaflet map settings.
    $this->leafletService->preProcessMapSettings($this->options);

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
            $map['geofield_cardinality'] = 1;
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
              $url = Url::fromRoute('leaflet_views.ajax_popup', $parameters, ['absolute' => TRUE]);
              $description = sprintf('<div class="leaflet-ajax-popup" data-leaflet-ajax-popup="%s" %s></div>',
                $url->toString(), LeafletAjaxPopupController::getPopupIdentifierAttribute($entity_type, $entity->id(), $this->options['view_mode'], $langcode));
              $map['settings']['ajaxPoup'] = TRUE;
              break;

            case '#rendered_view_fields':
              // Normal rendering via view/row fields (with labels options, formatters, classes, etc.).
              $renderRow = [
                "markup" => $this->view->rowPlugin->render($result),
              ];
              $description = !empty($this->options['description_field']) ? $this->renderer->renderPlain($renderRow) : '';
              break;

            default:
              // Row rendering of single specified field value (without labels).
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

            // Add its entity id, so that it might be referenced from outside.
            $feature['entity_id'] = $entity->id();
            // Attach also titles, they might be used later on.

            if ($this->options['name_field']) {
              // Decode any entities because JS will encode them again and
              // we don't want double encoding.
              $feature['label'] = $feature['popup'] = !empty($this->options['name_field']) ? Html::decodeEntities($this->rendered_fields[$result->index][$this->options['name_field']]) : '';
            }

            // Attach pop-ups if we have a description field.
            if (isset($description)) {
              if (empty($feature['popup'])) {
              $feature['popup'] = $description;
            }
              else {
                $feature['popup'] .= $description;
              }
            }

            // Eventually set the custom Marker icon (DivIcon, Icon Url or
            // Circle Marker).
            if ($feature['type'] === 'point' && isset($this->options['icon'])) {
              $feature['icon'] = $this->options['icon'];
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
                    if (!empty($this->options['icon']['shadowUrl'])) {
                      $feature['icon']['shadowUrl'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['icon']['shadowUrl'], $tokens));
                    }
                  }
                  break;
              }
            }

            // Associate dynamic path properties (token based) to each
            // feature, in case of not point.
            if ($feature['type'] !== 'point') {
              $feature['path'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['path'], $tokens));
            }

            // Allow modules to adjust the marker.
            \Drupal::moduleHandler()->alter('leaflet_views_feature', $feature, $result, $this->view->rowPlugin);
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
