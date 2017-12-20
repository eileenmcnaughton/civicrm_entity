<?php

namespace Drupal\civicrm_entity;

use Drupal\civicrm\Civicrm;

class CiviCrmApi implements CiviCrmApiInterface {

  /**
   * Constructs a new CiviCrmApi object.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    // Ensure CiviCRM is loaded and our function is available.
    if (!function_exists('civicrm_api3')) {
      $civicrm->initialize();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity, array $params = []) {
    $result = civicrm_api3($entity, 'get', $params);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity, array $params) {
    $result = civicrm_api3($entity, 'delete', $params);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function save($entity, array $params) {
    $result = civicrm_api3($entity, 'create', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity, $action = 'create') {
    $result = civicrm_api3($entity, 'getfields', ['action' => $action]);
    return $result['values'];
  }

}
