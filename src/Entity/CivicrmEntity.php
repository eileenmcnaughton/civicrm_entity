<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\civicrm_entity\Plugin\Field\ActivityEndDateFieldItemList;
use Drupal\civicrm_entity\Plugin\Field\BundleFieldItemList;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Entity class for CiviCRM entities.
 *
 * This entity class is not annotated. Plugin definitions are created during
 * the hook_entity_type_build() process. This allows for dynamic creation of
 * multiple entity types that use one single class, without creating redundant
 * class files and annotations.
 *
 * @see civicrm_entity_entity_type_build().
 */
class CivicrmEntity extends ContentEntityBase {

  /**
   * Flag to denote if the entity is currently going through Drupal CRUD hooks.
   *
   * We need to trigger the Drupal CRUD hooks when entities are edited in Civi,
   * but we need a way to ensure they aren't double triggered when already
   * going through the Drupal CRUD process.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  public $drupal_crud = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Set ::drupal_crud to indicate save is coming from Drupal.
    try {
      $this->drupal_crud = TRUE;
      $result = parent::save();
    }
    finally {
      $this->drupal_crud = FALSE;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Set ::drupal_crud to indicate delete is coming from Drupal.
    try {
      $this->drupal_crud = TRUE;
      parent::delete();
    }
    finally {
      $this->drupal_crud = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // If the `bundle` property is missing during the create operation, Drupal
    // will error – even if our bundle is computed on other required fields.
    // This ensures the values array has the bundle property set.
    $entity_type = $storage->getEntityType();
    if ($entity_type->hasKey('bundle')) {
      $bundle_property = $entity_type->get('civicrm_bundle_property');
      /** @var \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api */
      $civicrm_api = \Drupal::service('civicrm_entity.api');
      $options = $civicrm_api->getOptions($entity_type->get('civicrm_entity'), $bundle_property);

      if (isset($values[$entity_type->getKey('bundle')]) && $values[$entity_type->getKey('bundle')] === $entity_type->id()) {
        $raw_bundle_value = key($options);
      }
      else {
        $raw_bundle_value = $values[$bundle_property];
      }

      $bundle_value = $options[$raw_bundle_value];
      $transliteration = \Drupal::transliteration();
      $machine_name = SupportedEntities::optionToMachineName($bundle_value, $transliteration);
      $values[$entity_type->getKey('bundle')] = $machine_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    $civicrm_entity_info = SupportedEntities::getInfo()[$entity_type->id()];
    $civicrm_required_fields = !empty($civicrm_entity_info['required']) ? $civicrm_entity_info['required'] : [];
    $field_definition_provider = \Drupal::service('civicrm_entity.field_definition_provider');
    $civicrm_fields = \Drupal::service('civicrm_entity.api')->getFields($entity_type->get('civicrm_entity'), 'create');

    foreach ($civicrm_fields as $name => $civicrm_field) {
      // Apply any additional field data provided by the module.
      if (!empty($civicrm_entity_info['fields'][$name])) {
        $civicrm_field = $civicrm_entity_info['fields'][$name] + $civicrm_field;
      }

      $fields[$name] = $field_definition_provider->getBaseFieldDefinition($civicrm_field);
      $fields[$name]->setRequired(isset($civicrm_required_fields[$name]));

      if (str_starts_with($name, 'custom_') && $values = \Drupal::service('civicrm_entity.api')->getCustomFieldMetadata($name)) {
        $fields[$name]->setSetting('civicrm_entity_field_metadata', $values);
        $fields[$name]->setRequired((bool) $civicrm_field['is_required']);
      }
    }

    // Placing the bundle field here is a bit of a hack work around.
    // \Drupal\Core\Entity\ContentEntityStorageBase::initFieldValues will apply
    // default values to all empty fields. The computed bundle field will
    // provide a default value as well, for its related CiviCRM Entity field.
    // By placing this field last, we avoid conflict on setting of the default
    // value.
    if ($entity_type->hasKey('bundle')) {
      $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('string')
        ->setLabel($entity_type->getBundleLabel())
        ->setRequired(TRUE)
        ->setReadOnly(TRUE)
        ->setClass(BundleFieldItemList::class);
    }

    // Provide a computed base field that takes the activity start time and
    // appends the duration to calculated and end time.
    if ($entity_type->id() === 'civicrm_activity') {
      $fields['activity_end_datetime'] = BaseFieldDefinition::create('datetime')
        ->setLabel(t('Activity End Date'))
        ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME)
        ->setComputed(TRUE)
        ->setDisplayOptions('view', [
          'type' => 'datetime_default',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setClass(ActivityEndDateFieldItemList::class);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = parent::validate();

    $params = $this->civicrmApiNormalize();

    $civicrm_api = \Drupal::getContainer()->get('civicrm_entity.api');
    $civicrm_entity_type = $this->getEntityType()->get('civicrm_entity');
    $civicrm_violations = $civicrm_api->validate($civicrm_entity_type, $params);
    if (!empty($civicrm_violations)) {
      foreach (reset($civicrm_violations) as $civicrm_field => $civicrm_violation) {
        $definition = $this->getFieldDefinition($civicrm_field);
        $violation = new ConstraintViolation(
          str_replace($civicrm_field, $definition->getLabel(), $civicrm_violation['message']),
          str_replace($civicrm_field, $definition->getLabel(), $civicrm_violation['message']),
          [],
          '',
          $civicrm_field,
          $params[$civicrm_field]
        );
        $violations->add($violation);
      }
    }

    return $violations;
  }

  /**
   * Normalize CiviCRM API data.
   */
  public function civicrmApiNormalize() {
    $params = [];
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    foreach ($this->getFields() as $field_name => $items) {
      $items->filterEmptyItems();
      if ($items->isEmpty()) {
        continue;
      }

      $storage_definition = $items->getFieldDefinition()->getFieldStorageDefinition();

      if (!$storage_definition->isBaseField()) {
        // Do not try to pass any FieldConfig (or else) to CiviCRM API.
        continue;
      }

      $main_property_name = $storage_definition->getMainPropertyName();
      $list = [];
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($items as $delta => $item) {
        $main_property = $item->get($main_property_name);
        if ($main_property instanceof DateTimeIso8601 && !is_array($main_property->getValue())) {
          // CiviCRM wants the datetime in the timezone of the user, but Drupal
          // stores it in UTC.
          $value = (new \DateTime($main_property->getValue(), new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
        }
        else {
          $value = $main_property->getValue();
        }
        $list[$delta] = $value;
      }

      // Remove the wrapping array if the field is single-valued.
      if ($storage_definition->getCardinality() === 1) {
        $list = reset($list);
      }
      if (!empty($list)) {
        $params[$field_name] = $list;
      }
    }

    return $params;
  }

  public function getRawValue($field) {
    return $this->values[$field] ?? '';
  }

}
