<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Field Definition Provider class.
 */
class FieldDefinitionProvider implements FieldDefinitionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinition(array $civicrm_field) {
    if (($civicrm_field['name'] === 'contact_id' && $civicrm_field['title'] === 'Case Client')) {
      $civicrm_field['FKClassName'] = 'CRM_Contact_DAO_Contact';
    }

    if ($civicrm_field['name'] == 'id') {
      $field = $this->getIdentifierDefinition();
    }
    elseif (empty($civicrm_field['type'])) {
      $field = $this->getDefaultDefinition();
    }
    else {
      switch ($civicrm_field['type']) {
        case \CRM_Utils_Type::T_INT:
          // Check if this is an integer representing a serial identifier and
          // is a foreign key.
          if (isset($civicrm_field['FKClassName'])) {
            $foreign_key_dao = '\\' . $civicrm_field['FKClassName'];
            $table_name = $foreign_key_dao::getTableName();
            // Verify the foreign key table is a valid entity type.
            if (array_key_exists($table_name, SupportedEntities::getInfo())) {
              $field = BaseFieldDefinition::create('entity_reference')
                ->setSetting('target_type', $foreign_key_dao::getTableName())
                ->setSetting('handler', 'default');
              if (!empty($civicrm_field['pseudoconstant'])) {
                $field->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options');
              }
            }
            else {
              $field = $this->getIntegerDefinition($civicrm_field);
            }
          }
          elseif (isset($civicrm_field['data_type']) && $civicrm_field['data_type'] === 'ContactReference') {
            $field = BaseFieldDefinition::create('entity_reference')
              ->setSetting('target_type', 'civicrm_contact')
              ->setSetting('handler', 'default');

            if (isset($civicrm_field['serialize']) && $civicrm_field['serialize']) {
              $field
                ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
                ->setCustomStorage(TRUE);
            }
          }
          else {
            $field = $this->getIntegerDefinition($civicrm_field);
          }
          break;

        case \CRM_Utils_Type::T_BOOLEAN:
          $field = $this->getBooleanDefinition();
          break;

        case \CRM_Utils_Type::T_MONEY:
        case \CRM_Utils_Type::T_FLOAT:
          // @todo this needs to be handled.
          $field = BaseFieldDefinition::create('float');
          break;

        case \CRM_Utils_Type::T_STRING:
          $field = $this->getStringDefinition($civicrm_field);
          break;

        case \CRM_Utils_Type::T_CCNUM:
          $field = $this->getDefaultDefinition();
          break;

        case \CRM_Utils_Type::T_TEXT:
        case \CRM_Utils_Type::T_LONGTEXT:
        case \CRM_Utils_Type::T_BLOB:
          $field = $this->getTextDefinition($civicrm_field);
          break;

        case \CRM_Utils_Type::T_EMAIL:
          $field = $this->getEmailDefinition();
          break;

        case \CRM_Utils_Type::T_URL:
          $field = $this->getUrlDefinition();
          break;

        case \CRM_Utils_Type::T_DATE:
          $field = $this->getDateDefinition();
          break;

        case (\CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME):
        case \CRM_Utils_Type::T_TIMESTAMP:
          $field = $this->getDatetimeDefinition();
          break;

        case \CRM_Utils_Type::T_ENUM:
          $field = BaseFieldDefinition::create('map');
          break;

        case \CRM_Utils_Type::T_TIME:
          // @see https://github.com/civicrm/civicrm-core/blob/master/CRM/Core/DAO.php#L279
          // When T_TIME DAO throws error?
        default:
          $field = BaseFieldDefinition::create('any');
          break;
      }
    }

    if ($civicrm_field['name'] != 'id') {
      $field->setDisplayConfigurable('form', TRUE);
    }

    $field
      ->setDisplayConfigurable('view', TRUE)
      ->setLabel($civicrm_field['title'])
      ->setDescription($civicrm_field['description'] ?? '');

    if ($field->getType() != 'boolean') {
      $field->setRequired(isset($civicrm_field['api.required']) && (bool) $civicrm_field['api.required']);
    }
    if (isset($civicrm_field['api.default'])) {
      $field->setDefaultValue($field['api.default']);
    }

    return $field;
  }

  /**
   * Gets the identifier field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getIdentifierDefinition() {
    return BaseFieldDefinition::create('integer')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
  }

  /**
   * Gets an integer field definition.
   *
   * If the field uses pseudo constants, it is turned into a list_integer
   * and allowed values are set based on values that can be returned from the
   * CiviCRM API, as they are references.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getIntegerDefinition(array $civicrm_field) {
    if (!empty($civicrm_field['pseudoconstant']) && $civicrm_field['name'] != 'card_type_id') {
      $field = BaseFieldDefinition::create('list_integer')
        ->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'number_integer',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => 0,
        ]);
    }
    // Otherwise it is just a regular integer field.
    else {
      $field = BaseFieldDefinition::create('integer')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'number_integer',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'number',
          'weight' => 0,
        ]);
    }
    return $field;
  }

  /**
   * Gets a boolean field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getBooleanDefinition() {
    return BaseFieldDefinition::create('boolean')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 0,
      ]);
  }

  /**
   * Gets a string field definition.
   *
   * If the field uses pseudo constants, it is turned into a list_integer
   * and allowed values are set based on values that can be returned from the
   * CiviCRM API, as they are references.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getStringDefinition(array $civicrm_field) {
    if (!empty($civicrm_field['pseudoconstant'])) {
      $field = BaseFieldDefinition::create('list_string')
        ->setSetting('allowed_values_function', 'civicrm_entity_pseudoconstant_options')
        ->setDisplayOptions('view', [
          'type' => 'list_default',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => 0,
        ]);
    }
    // Otherwise it is just a regular integer field.
    else {
      $field = BaseFieldDefinition::create('string')
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => 0,
        ]);
    }

    if (isset($civicrm_field['html_type']) && $civicrm_field['html_type'] === 'CheckBox') {
      $field->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
      $field->setCustomStorage(TRUE);
    }

    return $field;
  }

  /**
   * Gets a text field definition.
   *
   * These are long text fields, and all default to being rich text. The
   * CiviCRM API does not provide a way to identify plain text or rich text
   * fields.
   *
   * The CiviCRM field info is passed so that the method can be override to
   * provide other specific logic in different implementations.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getTextDefinition(array $civicrm_field) {
    if ((!empty($civicrm_field['html']['type']) && $civicrm_field['html']['type'] == 'RichTextEditor') ||
      (!empty($civicrm_field['html_type']) && $civicrm_field['html_type'] == 'RichTextEditor')) {
      $field_type = 'text_long';
    }
    elseif (!empty($civicrm_field['description']) && strpos($civicrm_field['description'], 'Text and html allowed.') !== FALSE) {
      $field_type = 'text_long';
    }
    else {
      $field_type = 'string_long';
    }

    if ($civicrm_field['name'] === 'details' && $civicrm_field['entity'] === 'Case') {
      $field_type = 'text_long';
    }

    $field = BaseFieldDefinition::create($field_type)
      ->setDisplayOptions('view', [
        'type' => $field_type == 'string_long' ? 'basic_string' : 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => $field_type == 'string_long' ? 'string_textarea' : 'civicrm_entity_textarea',
        'weight' => 0,
        // If the default text formatter is CKEditor, this will be ignored.
        'rows' => $civicrm_field['rows'] ?? 5,
      ]);
    return $field;
  }

  /**
   * Gets an email field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getEmailDefinition() {
    return BaseFieldDefinition::create('email')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a URL field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getUrlDefinition() {
    return BaseFieldDefinition::create('uri')
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'uri_link',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a date field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDateDefinition() {
    return BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 0,
      ]);
  }

  /**
   * Gets a datetime field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDatetimeDefinition() {
    $field = BaseFieldDefinition::create('datetime')
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'weight' => 0,
      ]);
    return $field;
  }

  /**
   * Gets the default field definition.
   *
   * This is used for CiviCRM field types which are not mappable.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  protected function getDefaultDefinition() {
    return BaseFieldDefinition::create('string')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);
  }

}
