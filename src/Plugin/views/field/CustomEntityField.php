<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A field that displays entity field data for custom fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_custom_field")
 */
#[ViewsField("civicrm_entity_custom_field")]
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->civicrmApi = $container->get('civicrm_entity.api');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    $field_definition = $this->getFieldDefinition();

    if ($settings = $field_definition->getSetting('civicrm_entity_field_metadata')) {
      $this->fieldMetadata = $settings;

      if ($this->fieldMetadata['is_multiple'] || (isset($this->fieldMetadata['serialize']) && $this->fieldMetadata['serialize'])) {
        $this->fieldDefinition->setCardinality($this->fieldMetadata['max_multiple']);
      }
    }

    $options['entity_field'] = $options['field'];

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
