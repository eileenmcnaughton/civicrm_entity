<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    $civicrm_entity_info = SupportedEntities::getInfo()[$entity_type->id()];
    $civicrm_required_fields = !empty($civicrm_entity_info['required']) ? $civicrm_entity_info['required'] : [];
    $field_definition_provider = \Drupal::service('civicrm_entity.field_definition_provider');
    $civicrm_fields = \Drupal::service('civicrm_entity.api')->getFields($entity_type->get('civicrm_entity'), 'create');
    foreach ($civicrm_fields as $civicrm_field) {
      // Apply any additional field data provided by the module.
      if (!empty($civicrm_entity_info['fields'][$civicrm_field['name']])) {
        $civicrm_field = $civicrm_entity_info['fields'][$civicrm_field['name']] + $civicrm_field;
      }

      $fields[$civicrm_field['name']] = $field_definition_provider->getBaseFieldDefinition($civicrm_field);
      $fields[$civicrm_field['name']]->setRequired(isset($civicrm_required_fields[$civicrm_field['name']]));
    }
    $fields['drupal_crud'] = BaseFieldDefinition::create('boolean')->setComputed(TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $violations = parent::validate();

    $params = $this->civicrmApiNormalize();

    $civicrm_api = \Drupal::getContainer()->get('civicrm_entity.api');
    $civicrm_violations = $civicrm_api->validate('event', $params);
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
        if ($main_property instanceof DateTimeIso8601) {
          // CiviCRM wants the datetime in the timezone of the user, but Drupal
          // stores it in UTC.
          $value = (new \DateTime($main_property->getValue(), new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone(\drupal_get_user_timezone()))->format('Y-m-d H:i:s');
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

}
