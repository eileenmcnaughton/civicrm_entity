<?php

namespace Drupal\civicrm_entity;

interface CiviCrmApiInterface {

  /**
   * Get an entity from CiviCRM.
   *
   * @param $entity
   * @param array $params
   *
   * @return mixed
   */
  public function get($entity, array $params = []);

  /**
   * Delete an entity in CiviCRM
   *
   * @param $entity
   * @param array $params
   *
   * @return mixed
   */
  public function delete($entity, array $params);

  /**
   * Save and update an entity in CiviCRM
   *
   * @param $entity
   * @param array $params
   *
   * @return mixed
   */
  public function save($entity, array $params);

  /**
   * Get fields from the CiviCRM entity.
   *
   * @param $entity
   * @param string $action
   *
   * @return mixed
   */
  public function getFields($entity, $action = 'create');

  /**
   * Get options for the CiviCRM entity field.
   * @param $entity
   * @param $field_name
   *
   * @return mixed
   */
  public function getOptions($entity, $field_name);

}
