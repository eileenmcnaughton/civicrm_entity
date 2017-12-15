<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the comment entity class.
 *
 * @todo need to alter definition based on known fields.
 *
 * @ContentEntityType(
 *   id = "civicrm_event",
 *   civicrm_entity = "event",
 *   label = @Translation("CiviCRM Event"),
 *   label_singular = @Translation("event"),
 *   label_plural = @Translation("events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count event",
 *     plural = "@count events",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\civicrm_entity\CiviEntityStorage",
 *     "access" = "Drupal\comment\CommentAccessControlHandler",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "subject",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/events/{civicrm_events}",
 *     "delete-form" = "/events/{civicrm_events}/delete",
 *     "edit-form" = "/events/{civicrm_events}/edit",
 *     "create" = "/events",
 *   },
 * )
 */
class Events extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $civicrm_fields = civicrm_api3($entity_type->get('civicrm_entity'), 'getfields', ['action' => 'create']);
    foreach ($fields['values'] as $civicrm_field) {
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
      $field = BaseFieldDefinition::create('string');
    }
    else {
      switch ($civicrm_field['type']) {
        case \CRM_Utils_Type::T_INT:
          $field = BaseFieldDefinition::create('integer');
          break;

        case \CRM_Utils_Type::T_BOOLEAN:
          $field = BaseFieldDefinition::create('boolean');
          break;

        case \CRM_Utils_Type::T_MONEY:
        case \CRM_Utils_Type::T_FLOAT:
          $field = BaseFieldDefinition::create('float');
          break;

        case \CRM_Utils_Type::T_TEXT:
        case \CRM_Utils_Type::T_STRING:
        case \CRM_Utils_Type::T_LONGTEXT:
        case \CRM_Utils_Type::T_CCNUM:
          $field = BaseFieldDefinition::create('text');
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
          return array('type' => 'varchar', 'mysql_type' => 'datetime');

        case \CRM_Utils_Type::T_ENUM:
          return array('type' => 'varchar', 'mysql_type' => 'enum');

        case \CRM_Utils_Type::T_BLOB:
        case \CRM_Utils_Type::T_MEDIUMBLOB:
          return array('type' => 'blob');

        case \CRM_Utils_Type::T_TIMESTAMP:
          $field = BaseFieldDefinition::create('timestamp');
          break;
      }
    }


    $field
      ->setLabel($civicrm_field['title'])
      ->setDescription($civicrm_field['description'])
      ->setRequired($civicrm_field['required']);

    return $field;
  }

}
