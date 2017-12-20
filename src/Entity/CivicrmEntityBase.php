<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

abstract class CivicrmEntityBase extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $civicrm_fields = \Drupal::service('civicrm_entity.api')->getFields($entity_type->get('civicrm_entity'));
    foreach ($civicrm_fields as $civicrm_field) {
      $fields[$civicrm_field['name']] = self::createBaseFieldDefinition($civicrm_field);
    }

    return $fields;
  }

  protected static function createBaseFieldDefinition(array $civicrm_field) {
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
          'weight' => -5,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => -5,
        ]);
    }
    else {
      switch ($civicrm_field['type']) {
        case \CRM_Utils_Type::T_INT:
          $field = BaseFieldDefinition::create('integer')
            ->setDisplayOptions('view', [
              'label' => 'hidden',
              'type' => 'integer',
              'weight' => 0,
            ])
            ->setDisplayOptions('form', [
              'type' => 'number',
              'weight' => 20,
            ]);
          break;

        case \CRM_Utils_Type::T_BOOLEAN:
          $field = BaseFieldDefinition::create('boolean')
            ->setDisplayOptions('form', [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
              'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE);
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
            'weight' => 10,
          ])
          ->setDisplayConfigurable('view', TRUE)
          ->setDisplayOptions('form', [
            'type' => 'string_textfield',
            'weight' => 10,
          ])
          ->setDisplayConfigurable('form', TRUE);
        break;

        case \CRM_Utils_Type::T_LONGTEXT:
          $field = BaseFieldDefinition::create('text_long')
            ->setDisplayOptions('view', [
              'type' => 'text_default',
              'weight' => 10,
            ])
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', [
              'type' => 'text_textfield',
              'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);
          break;

        case \CRM_Utils_Type::T_EMAIL:
          $field = BaseFieldDefinition::create('email');
          break;

        case \CRM_Utils_Type::T_URL:
          $field = BaseFieldDefinition::create('uri');
          break;

        case \CRM_Utils_Type::T_DATE:
        case \CRM_Utils_Type::T_TIME:
        case (\CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME):
          $field = BaseFieldDefinition::create('datetime');
          break;

        case \CRM_Utils_Type::T_ENUM:
          $field = BaseFieldDefinition::create('map');
          break;

        case \CRM_Utils_Type::T_TIMESTAMP:
          $field = BaseFieldDefinition::create('timestamp');
          break;

        default:
          $field = BaseFieldDefinition::create('any');
          break;
      }
    }

    $field
      ->setLabel($civicrm_field['title'])
      ->setDescription(isset($civicrm_field['description']) ? $civicrm_field['description'] : '');

    if ($field->getType() != 'boolean') {
      $field->setRequired(isset($civicrm_field['required']) && (bool) $civicrm_field['required']);
    }

    return $field;
  }
}
