<?php

/**
 * Entity Controller for CiviCRM entities
 */
class CivicrmEntityController extends EntityAPIController {

 public function __construct($entityType) {
    parent::__construct($entityType);
  }

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
    $passed_ids = !empty($ids) && !is_null($ids[0]) ? array_flip((array) $ids) : FALSE;
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
        $queried_entities = array();
        if ($conditions) {
          $queried_entities += $this->loadEntities($conditions);
        }
        foreach ($ids as $id) {
          // we can't rely on civicrm api accepting the 'IN' => array(1,5,6) for all entities
          $queried_entities += $this->loadEntities(array('id' => $id));
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

    try {
      $entity->is_new = !empty($entity->is_new) || empty($entity->{$this->idKey});
      // @TODO should we call this hook when drupal saves (as opposed
      // to Civi?) ditto insert, update.

      $this->invoke('presave', $entity);

      $params = (array)$entity;
      unset($params['is_new']);

      /*foreach($params as $key => $value){
        if (substr($key, 7) === 'custom_' && ( $value == '' || is_null($value)) ) {
          unset($params[$key]);
        }
      }*/

      $params['version'] = 3;
      $params['sequential'] = 1;

      if ($entity->is_new) {
        $result = civicrm_api(substr($this->entityType, 8), 'create', $params);

      }
      else {
        $result = civicrm_api(substr($this->entityType, 8), 'update', $params);

      }

      if(isset($entity->is_new)) {
        $is_new = $entity->is_new;
        unset($entity->is_new);
      }
      unset($entity->is_new_revision);
      unset($entity->original);

      if (!civicrm_error($result)) {
        if($is_new) {
          $entity->id= $result['values'][0]['id'];
          field_attach_update($this->entityType, $entity);
         // $this->invoke('insert', $entity);
        }
        else {
          field_attach_update($this->entityType, $entity);
          $this->invoke('update', $entity);
        }

        foreach ($result['values'][0] as $key => $value) {
          $entity->key = $value;
        }
        return new CivicrmEntity(array_keys($result['values']), $this->entityType);
      }
      throw new Exception($result['error_message']);
    }
    catch (Exception $e) {
      watchdog_exception($this->entityType, $e);
      throw $e;
    }
  }

  public function delete($ids, DatabaseTransaction $transaction = NULL) {
    if (!civicrm_initialize()) {
      throw new Exception('civicrm inaccessible');
    }
    $entities = entity_load($this->entityType,$ids);
    foreach($ids as $id) {
      $params['version'] = 3;
      $params['id'] = $id;

      try {

        $entity = $entities[$id];

        $this->invoke('predelete', $entity);
        $result = civicrm_api(substr($this->entityType, 8), 'delete', $params);

        if (!civicrm_error($result)) {
          field_attach_delete($this->entityType, $entity);
           // $this->invoke('delete', $entity);
          return;
        }
        throw new Exception($result['error_message']);
      }
      catch (Exception $e) {
        watchdog_exception($this->entityType, $e);
        throw $e;
      }
    } //end foreach id
  }

  public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
    $entity->content = $content;
    $build = parent::buildContent($entity,$view_mode,$langcode,$content);
    $langcode = isset($langcode) ? $langcode : $GLOBALS['language_content']->language;

    // Add in fields.
    if (!empty($this->entityInfo['fieldable'])) {
      // Perform the preparation tasks if they have not been performed yet.
      // An internal flag prevents the operation from running twice.
      $key = isset($entity->{$this->idKey}) ? $entity->{$this->idKey} : NULL;
      field_attach_prepare_view($this->entityType, array($key => $entity), $view_mode);
      $entity->content = field_attach_view($this->entityType, $entity, $view_mode, $langcode);
    }

    $temp_build = $entity->content;
    unset($entity->content);

    foreach($temp_build as $key => $value) {
      $build[$key] = $value;
    }

    if(module_exists('ds')) {
      //load display suite css if layout is enabled for view mode
      static $loaded_css = array();
      $layout = ds_get_layout($this->entityType, $this->entityType, $view_mode);

      // Add path to css file for this layout and disable block regions if necessary.
      if (isset($layout['css']) && !isset($loaded_css[$layout['path'] . '/' . $layout['layout'] . '.css'])) {
        // Register css file.
        $loaded_css[$layout['path'] . '/' . $layout['layout'] . '.css'] = TRUE;
        // Add css file.
        if (isset($layout['module']) && $layout['module'] == 'panels') {
          $build['#attached']['css'][] = $layout['path'] . '/' . $layout['panels']['css'];
        } else {
          $build['#attached']['css'][] = $layout['path'] . '/' . $layout['layout'] . '.css';
        }
      }
    }
    return $build;
  }

  /**
   * Load entities to an array.
   *
   * @param array $condition
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  protected function loadEntities($condition) {
    $entities = array();
    $fields = civicrm_api3($this->entityInfo['description'], 'getfields', array('action' => 'get'));
    if (!empty($fields['values'])) {
      $condition['return'] = array_keys($fields['values']);
    }

    $civicrm_entities = civicrm_api3($this->entityInfo['description'], 'get', $condition);
    if ($civicrm_entities['count']) {
      foreach ($civicrm_entities['values'] as $id => $civicrm_entity) {
        // @TODO improve this casting.
        $entities[$id] = new CivicrmEntity($civicrm_entity, $this->entityType);

      }
      return $entities;
    }
    return $entities;
  }

}
