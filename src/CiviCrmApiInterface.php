<?php

namespace Drupal\civicrm_entity;

/**
 * The Drupal to CiviCRM API bridge.
 */
interface CiviCrmApiInterface {

  /**
   * Get an entity from CiviCRM.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   Optional additional parameters.
   *
   * @return array
   *   The entity data.
   */
  public function get($entity, array $params = []);

  /**
   * Delete an entity in CiviCRM.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   The params, an array of ID mappings.
   *
   * @return array
   *   The CiviCRM API response.
   */
  public function delete($entity, array $params);

  /**
   * Save and update an entity in CiviCRM.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   The array of field values.
   *
   * @return array
   *   The CiviCRM API response.
   */
  public function save($entity, array $params);

  /**
   * Get fields from the CiviCRM entity.
   *
   * @param string $entity
   *   The entity name.
   * @param string $action
   *   The action.
   *
   * @return array
   *   The array of field information.
   */
  public function getFields($entity, $action = 'create');

  /**
   * Get options for the CiviCRM entity field.
   *
   * @param string $entity
   *   The entity name.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The array of options.
   */
  public function getOptions($entity, $field_name);

  /**
   * Get the count of entries for an entity.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   The array of field values.
   *
   * @return int
   *   The number of entities.
   */
  public function getCount($entity, array $params = []);

  /**
   * Get single from the CiviCRM entity.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   Optional additional parameters.
   *
   * @return array
   *   The array of values.
   */
  public function getSingle($entity, array $params = []);

  /**
   * Get values from the CiviCRM entity.
   *
   * @param string $entity
   *   The entity name.
   * @param array $params
   *   Optional additional parameters.
   *
   * @return array
   *   The array of values.
   */
  public function getValue($entity, array $params = []);

  /**
   * Initialize the CiviCRM API.
   */
  public function civicrmInitialize();

  /**
   * Retrieve custom field metadata for a field.
   *
   * @param string $field_name
   *   The field name e.g. custom_*.
   *
   * @return array
   *   Array of field metadata.
   */
  public function getCustomFieldMetadata($field_name);

}
