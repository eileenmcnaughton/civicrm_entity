<?php

namespace Drupal\civicrm_entity;

use Drupal\civicrm\Civicrm;

/**
 * CiviCRM API implementation.
 */
class CiviCrmApi implements CiviCrmApiInterface {

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Constructs a new CiviCrmApi object.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity, array $params = []) {
    $this->initialize();
    $result = civicrm_api3($entity, 'get', $params);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity, array $params) {
    $this->initialize();
    $result = civicrm_api3($entity, 'delete', $params);
    return $result['values'];
  }

  public function validate($entity, $params) {
    $this->initialize();
    if (!function_exists('_civicrm_api3_validate')) {
      require_once 'api/v3/utils.php';
    }
    return _civicrm_api3_validate($entity, 'create', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function save($entity, array $params) {
    $this->initialize();
    $result = civicrm_api3($entity, 'create', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity, $action = '') {
    $this->initialize();
    $result = civicrm_api3($entity, 'getfields', [
      // 'sequential' => 1,
      'action' => $action,
    ]);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($entity, $field_name) {
    $this->initialize();
    $result = civicrm_api3($entity, 'getoptions', ['field' => $field_name]);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCount($entity, array $params = []) {
    $this->initialize();
    $result = civicrm_api3($entity, 'getcount', $params);
    return is_int($result) ? $result : $result['result'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSingle($entity, array $params = []) {
    $this->initialize();
    $result = civicrm_api3($entity, 'getsingle', $params);

    if (isset($result['is_error'])) {
      return [];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($entity, array $params = []) {
    $this->initialize();
    $result = civicrm_api3($entity, 'getvalue', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function civicrmInitialize() {
    $this->civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomFieldMetadata($field_name) {
    $field_name = explode('_', $field_name, 2);

    // There are field names that are just single names that can't be broken
    // down into two values.
    if (count($field_name) < 2) {
      return FALSE;
    }

    list(, $id) = $field_name;

    try {
      $values = $this->get('CustomField', ['id' => $id, 'is_active' => 1]);
      $values = reset($values);

      if (!empty($values)) {
        // Include information from group.
        if (isset($values['custom_group_id']) && ($custom_group_values = $this->get('CustomGroup', ['sequential' => 1, 'id' => $values['custom_group_id']]))) {
          $custom_group_values = reset($custom_group_values);

          $values += [
            'title' => $custom_group_values['title'],
            'extends' => $custom_group_values['extends'],
            'table_name' => $custom_group_values['table_name'],
            'is_multiple' => (bool) $custom_group_values['is_multiple'],
            'max_multiple' => $custom_group_values['max_multiple'] ?? -1,
          ];
        }

        return $values;
      }

      return FALSE;
    }
    catch (\CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Ensures that CiviCRM is loaded and API function available.
   */
  protected function initialize() {
    if (!function_exists('civicrm_api3')) {
      $this->civicrm->initialize();
    }
  }

}
