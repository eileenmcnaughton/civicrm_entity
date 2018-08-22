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
  public function getCount($entity, array $params);

  /**
   * Convert possibly camel name to underscore separated entity name.
   *
   * @see _civicrm_api_get_entity_name_from_camel()
   *
   * @TODO Why don't we just call the above function directly?
   * Because the function is officially 'likely' to change as it is an internal
   * api function and calling api functions directly is explicitly not
   * supported.
   *
   * @param string $entity
   *   Entity name in various formats e.g:
   *     Contribution => contribution,
   *     OptionValue => option_value,
   *     UFJoin => uf_join.
   *
   * @return string
   *   $entity entity name in underscore separated format
   */
  public function getEntityNameFromCamel($entity);


}
