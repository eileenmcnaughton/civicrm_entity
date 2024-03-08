<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A field that displays entity field data for custom fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_custom_field")
 */
class CustomEntityField extends EntityField {
  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $fieldDefinition;

  /**
   * The custom values.
   *
   * @var array
   */
  protected $customValues;

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * The field metadata.
   *
   * @var array
   */
  protected $fieldMetadata;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL, EntityFieldManagerInterface $entity_field_manager = NULL, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer, $entity_repository, $entity_field_manager);
    $this->civicrmApi = $civicrm_api;
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
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    $field_definition = $this->getFieldDefinition();

    if ($settings = $field_definition->getSetting('civicrm_entity_field_metadata')) {
      $this->fieldMetadata = $settings;

      if ($this->fieldMetadata['is_multiple'] || (isset($this->fieldMetadata['serialize']) && $this->fieldMetadata['serialize'])) {
        $this->fieldDefinition->setCardinality($this->fieldMetadata['max_multiple']);
      }
    }
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    unset($options['click_sort_column']);

    if (in_array($this->fieldMetadata['html_type'], ['Multi-Select', 'CheckBox'])) {
      $options['type']['default'] = 'civicrm_entity_custom_multi_value';
    }
    if ($this->fieldMetadata['data_type'] == 'ContactReference') {
      $options['type']['default'] = 'civicrm_entity_contact_reference';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['click_sort_column']);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->ensureMyTable();

    if ($this->fieldMetadata && $this->fieldMetadata['column_name']) {
      $this->query->addOrderBy(NULL, NULL, $order, $this->tableAlias . '.' . $this->fieldMetadata['column_name']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($use_groupby = FALSE) {
    $fields = $this->additional_fields;

    if ($use_groupby && $this->fieldMetadata && $this->fieldMetadata['column_name']) {
      $options = [];
      if ($this->options['group_column'] != 'entity_id') {
        $options = [$this->options['group_column'] => $this->options['group_column']];
      }

      $options += is_array($this->options['group_columns']) ? $this->options['group_columns'] : [];

      foreach ($options as $column) {
        $fields[$column] = $this->fieldMetadata['column_name'];
      }

      $this->group_fields = $fields;
    }

    if ($this->add_field_table($use_groupby)) {
      $this->ensureMyTable();
      $fields['id'] = 'id';
      $this->addAdditionalFields($fields);
    }

    $this->getEntityFieldRenderer()->query($this->query, $this->relationship);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $display = [
      'type' => $this->options['type'],
      'settings' => $this->options['settings'],
      'label' => 'hidden',
    ];

    if (($entity = $this->getEntity($values)) && isset($entity->{$this->definition['field_name']})) {
      $entity = $this->createEntity($entity);

      if (isset($this->aliases['id']) && isset($values->{$this->aliases['id']})) {
        $values->delta = $this->getDelta($values->{$this->aliases['id']});
      }

      $build_list = $entity->{$this->definition['field_name']}->view($display);
    }
    else {
      $build_list = NULL;
    }

    if (!$build_list) {
      return [];
    }

    if ($this->options['field_api_classes']) {
      return [['rendered' => $this->renderer->render($build_list)]];
    }

    $items = [];
    $bubbleable = BubbleableMetadata::createFromRenderArray($build_list);
    foreach (Element::children($build_list) as $delta) {
      BubbleableMetadata::createFromRenderArray($build_list[$delta])
        ->merge($bubbleable)
        ->applyTo($build_list[$delta]);
      $items[$delta] = [
        'rendered' => $build_list[$delta],
        'raw' => $build_list['#items'][$delta],
      ];
    }

    return $this->prepareItemsByDelta($items);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareItemsByDelta(array $all_values) {
    if ($this->limit_values) {
      $row = $this->view->result[$this->view->row_index];

      if (!$this->options['group_rows'] && isset($all_values[$row->delta]) && is_numeric($row->delta)) {
        return [$all_values[$row->delta]];
      }
    }

    return parent::prepareItemsByDelta($all_values);
  }

  /**
   * Populate the entity from CiviCRM API.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be processed.
   *
   * @return null|\Drupal\Core\Entity\FieldableEntityInterface
   *   Returns the processed entity.
   */
  protected function createEntity(EntityInterface $entity) {
    $processed_entity = clone $entity;

    try {
      $result = $this->civicrmApi->get('CustomValue', [
        'sequential' => 1,
        'return' => [$this->definition['field_name']],
        'entity_id' => $entity->id(),
        'entity_table' => $entity->getEntityTypeId(),
      ]);

      if (!empty($result)) {
        $result = reset($result);
        $result = array_filter($result, function ($key) {
          return is_int($key);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($result)) {
          if (isset($result[0]) && is_array($result[0])) {
            $result = reset($result);
          }

          $this->customValues = $result;
          $field_definition = $this->getFieldDefinition();

          $processed_entity->{$this->definition['field_name']} = array_map(function ($value) use ($field_definition) {
            return $this->getItemValue($value, $field_definition);
          }, $result);
        }
      }
      elseif ($this->getFieldDefinition()->getType() == 'boolean') {
        // CiviCRM API3 will return no result when quering a custom
        // field row that has no values. In this case we want to set
        // the field to NULL otherwise it defaults to false.
        $processed_entity->{$this->definition['field_name']} = NULL;
      }
    }
    catch (\CiviCRM_API3_Exception $e) {
      // Don't do anything.
    }

    return $processed_entity;
  }

  /**
   * Process each value depending on the set definition type.
   *
   * @param mixed $value
   *   The value returned by CiviCRM API.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return mixed
   *   The processed item value.
   *
   * @see \Drupal\civicrm_entity\CiviEntityStorage::initFieldValues()
   */
  protected function getItemValue($value, FieldDefinitionInterface $definition) {
    if (is_null($value)) {
      return NULL;
    }

    switch ($definition->getType()) {
      case 'datetime':
        if (!empty($value)) {
          return $this->convertToUtc($definition, $value);
        }
        break;

      case 'boolean':
        // For booleans we want to convert the empty string to NULL,
        // to avoid it being displayed as false.
        if ($value == '') {
          return NULL;
        }
        break;
    }

    return $value;
  }

  /**
   * Guess the delta based on the custom values.
   *
   * @param int $id
   *   The ID of the custom value.
   *
   * @return int
   *   The guessed delta.
   */
  protected function getDelta($id) {
    if ($this->customValues) {
      return array_search($id, array_keys($this->customValues));
    }

    return 0;
  }

  /**
   * Check if date field should be converted to UTC or not.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   * @param string $date_value
   *   The date value.
   *
   * @return string
   *   The converted value.
   */
  public function convertToUtc(FieldDefinitionInterface $definition, $date_value) {
    $datetime_format = $definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $default_timezone = date_default_timezone_get();

    $utc = TRUE;
    // If the field is custom and meant to store only year value,
    // Avoid converting to any timezone and display it as stored in database.
    if (strpos($definition->getName(), "custom_") === 0) {
      [, $custom_field_id] = explode('_', $definition->getName());
      $params = [
        'sequential' => 1,
        'id' => $custom_field_id,
      ];
      $date_field = $this->civicrmApi->get('CustomField', $params);
      if (empty($date_field[0]['time_format'])) {
        $utc = FALSE;
      }
    }

    if ($utc) {
      return (new \DateTime($date_value, new \DateTimeZone($default_timezone)))->setTimezone(new \DateTimeZone('UTC'))->format($datetime_format);
    }
    return (new \DateTime($date_value, new \DateTimeZone($default_timezone)))->format($datetime_format);
  }

}
