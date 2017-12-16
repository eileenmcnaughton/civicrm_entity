<?php

namespace Drupal\civicrm_entity;

class CiviCrmApi {

  public function get($entity, array $params = []) {
    $result = civicrm_api3($entity, 'get', $params);
    return $result['values'];
  }

  public function delete($entity, array $params) {
    $result = civicrm_api3($entity, 'delete', $params);
    return $result['values'];
  }

  public function save($entity, array $params) {
    $result = civicrm_api3($entity, 'create', $params);
    return $result;
  }

  public function getFields($entity, $action = 'create') {
    $result = civicrm_api3($entity, 'getfields', ['action' => $action]);
    return $result['values'];
  }

}
