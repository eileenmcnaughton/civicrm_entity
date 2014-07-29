<?php

/**
 * Entity Controller for CiviCRM entities
 */
class CivicrmEntityController extends EntityAPIController {

  /**
   * Implements DrupalEntityControllerInterface::load().
   *
   * @param array $ids
   * @param array $conditions
   *
   * @return array
   */
  public function load($ids = array(), $conditions = array()) {
    $entities = array();
    if (!civicrm_initialize(TRUE)) {
      return;
    }
    // Not sure about revisioning out at this stage - I don't know if
    // it could have any later use. Revisions are not statically
    // cached, and require a different query to other conditions, so
    // separate the revision id into its own variable.
    if ($this->revisionKey && isset($conditions[$this->revisionKey])) {
      $revision_id = $conditions[$this->revisionKey];
      unset($conditions[$this->revisionKey]);
    }
    else {
      $revision_id = FALSE;
    }

    /*
    * this seems 'harmless' - but not necessarily relevant?
    * ie. deals with caching on the drupal side
    */
    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed. The $ids array is reduced as items are loaded from cache,
    // and we need to know if it's empty for this reason to avoid querying the
    // database when all requested entities are loaded from cache.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    // Try to load entities from the static cache, if the entity type supports
    // static caching.
    if ($this->cache && !$revision_id) {
      $entities += $this->cacheGet($ids, $conditions);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    /*
    * OK - here is where we will actually 'Do' something that is Civi-Specific
    * In drupal land $ids = FALSE would load all - let's only do specific
    */
    // Load any remaining entities from the database. This is the case if $ids
    // is set to FALSE (so we load all entities), if there are any ids left to
    // load, if loading a revision, or if $conditions was passed without $ids.
    if ($ids === FALSE || $ids || $revision_id || ($conditions && !$passed_ids)) {
      // Build the query.
      try {
        if ($conditions) {
          $civicrm_entities = civicrm_api3($this->entityInfo['description'], 'get', $conditions);
        }
        foreach ($ids as $id) {
          // we can't rely on civicrm api accepting the 'IN' => array(1,5,6) for all entities
          $civicrm_entities = civicrm_api3($this->entityInfo['description'], 'get', array('id' => $id));
        }
        foreach ($civicrm_entities['values'] as $id => $civicrm_entity) {
          // @TODO improve this casting.
          $queried_entities[$id] = new CivicrmEntity($civicrm_entity, $this->entityType);
        }
      }
      catch (Exception $e) {
        watchdog('civicrm_entity', 'Failed to load ' . $this->entityInfo['description'], $conditions);
      }
    }

    // Pass all entities loaded from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->attachLoad($queried_entities, $revision_id);
      $entities += $queried_entities;
    }

    if ($this->cache) {
      // Add entities to the cache if we are not loading a revision.
      if (!empty($queried_entities) && !$revision_id) {
        $this->cacheSet($queried_entities);
      }
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->{$this->idKey}] = $entity;
      }
      $entities = $passed_ids;
    }
    return $entities;
  }


  /**
   * Implements EntityAPIControllerInterface.
   *
   * @param $entity
   * @param DatabaseTransaction $transaction
   *   Optionally a DatabaseTransaction object to use. Allows
   *   overrides to pass in their transaction object.
   *
   * @throws Exception
   */
  public function save($entity, DatabaseTransaction $transaction = NULL) {
    if (!civicrm_initialize()) {
      throw new Exception('civicrm inaccessible');
    }
    $params = (array)$entity;
    unset($params['is_new']);
    $params['version'] = 3;
    $params['sequential'] = 1;

    try {
      $entity->is_new = !empty($entity->is_new) || empty($entity->{$this->idKey});
      // @TODO should we call this hook when drupal saves (as opposed
      // to Civi?) ditto insert, update.
      $this->invoke('presave', $entity);
      if ($entity->is_new) {
        $result = civicrm_api(substr($this->entityType, 8), 'create', $params);
        // $this->invoke('insert', $entity);
      }
      else {
        $result = civicrm_api(substr($this->entityType, 8), 'update', $params);
        // $this->invoke('update', $entity);
      }

      unset($entity->is_new);
      unset($entity->is_new_revision);
      unset($entity->original);
      if (!civicrm_error($result)) {
        return (object) $result['values'][0];
      }
      throw new Exception($result['error_message']);
    }
    catch (Exception $e) {
      watchdog_exception($this->entityType, $e);
      throw $e;
    }
  }
}
