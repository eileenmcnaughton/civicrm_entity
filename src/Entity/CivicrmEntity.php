<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

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

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $civicrm_fields = \Drupal::service('civicrm_entity.api')->getFields($entity_type->get('civicrm_entity'));
    foreach ($civicrm_fields as $civicrm_field) {
      $fields[$civicrm_field['name']] = self::createBaseFieldDefinition($civicrm_field, $entity_type->get('civicrm_entity'));
    }

    return $fields;
  }

  protected static function createBaseFieldDefinition(array $civicrm_field, $civicrm_entity_id) {
    if ($civicrm_field['name'] == 'id') {
      $field = BaseFieldDefinition::create('integer')
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }
    elseif (empty($civicrm_field['type'])) {
      $field = BaseFieldDefinition::create('string')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
        ]);
    }
    else {
      switch ($civicrm_field['type']) {
        case \CRM_Utils_Type::T_INT:
          // If this field has `pseudoconstant` it is a reference to values in
          // civicrm_option_value.
          if (!empty($civicrm_field['pseudoconstant']) && $civicrm_field['name'] != 'card_type_id') {
            $field = BaseFieldDefinition::create('list_integer')
              ->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options')
              ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'integer',
              ])
              ->setDisplayOptions('form', [
                'type' => 'options_select',
              ]);
          }
          // Otherwise it is just a regular integer field.
          else {
            $field = BaseFieldDefinition::create('integer')
              ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'integer',
              ])
              ->setDisplayOptions('form', [
                'type' => 'number',
              ]);
          }

          break;

        case \CRM_Utils_Type::T_BOOLEAN:
          $field = BaseFieldDefinition::create('boolean')
            ->setDisplayOptions('form', [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
            ]);
          break;

        case \CRM_Utils_Type::T_MONEY:
        case \CRM_Utils_Type::T_FLOAT:
          $field = BaseFieldDefinition::create('float');
          break;

        case \CRM_Utils_Type::T_STRING:
        case \CRM_Utils_Type::T_TEXT:
        case \CRM_Utils_Type::T_CCNUM:
        $field = BaseFieldDefinition::create('string')
          ->setDisplayOptions('view', [
            'type' => 'text_default',
          ])
          ->setDisplayOptions('form', [
            'type' => 'string_textfield',
          ]);
        break;

        case \CRM_Utils_Type::T_LONGTEXT:
          $field = BaseFieldDefinition::create('text_long')
            ->setDisplayOptions('view', [
              'type' => 'text_default',
            ])
            ->setDisplayOptions('form', [
              'type' => 'text_textfield',
            ]);
          break;

        case \CRM_Utils_Type::T_EMAIL:
          $field = BaseFieldDefinition::create('email')
            ->setDisplayOptions('view', [
              'label' => 'above',
              'type' => 'string',
              'weight' => 0,
            ])
            ->setDisplayOptions('form', [
              'type' => 'email_default',
            ]);
          break;

        case \CRM_Utils_Type::T_URL:
          $field = BaseFieldDefinition::create('uri')
            ->setDisplayOptions('form', [
              'type' => 'uri',
              'weight' => -3,
            ]);
          break;

        // @todo this needs display options... thought they were set?
        case \CRM_Utils_Type::T_DATE:
        case \CRM_Utils_Type::T_TIME:
        case (\CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME):
          $field = BaseFieldDefinition::create('datetime');
          break;

        case \CRM_Utils_Type::T_ENUM:
          $field = BaseFieldDefinition::create('map');
          break;

        case \CRM_Utils_Type::T_TIMESTAMP:
          $field = BaseFieldDefinition::create('timestamp')
            ->setDisplayOptions('view', [
              'label' => 'hidden',
              'type' => 'timestamp',
              'weight' => 0,
            ])
            ->setDisplayOptions('form', [
              'type' => 'datetime_timestamp',
              'weight' => 10,
            ]);
          break;

        default:
          $field = BaseFieldDefinition::create('any');
          break;
      }
    }

    $field
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setLabel($civicrm_field['title'])
      ->setDescription(isset($civicrm_field['description']) ? $civicrm_field['description'] : '');

    if ($field->getType() != 'boolean') {
      $field->setRequired(isset($civicrm_field['required']) && (bool) $civicrm_field['required']);
    }

    return $field;
  }
}
