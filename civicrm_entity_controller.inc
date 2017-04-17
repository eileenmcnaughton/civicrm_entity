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
        if (!empty($ids)) {
          foreach ($ids as $id) {
            // we can't rely on civicrm api accepting the 'IN' => array(1,5,6) for all entities
            $queried_entities += $this->loadEntities(array('id' => $id));
          }
        }
      } catch (Exception $e) {
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
   * @return object
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

      // temporary hack for activity saving issues
      // https://www.drupal.org/node/2736153
      if ($this->entityType == 'civicrm_activity' && isset($entity->campaign_id) && $entity->campaign_id == 0) {
        $entity->campaign_id = NULL;
      }

      $params = get_object_vars($entity);

      $params_to_unset = array(
        'is_new',
        'rdf_mapping',
        'original',
        'submit',
        'form_build_id',
        'form_token',
        'form_id',
        'op',
        'changed',
      );
      foreach ($params as $key => $value) {
        // unset some drupal form stuff
        if (in_array($key, $params_to_unset)) {
          unset($params[$key]);
        }
        // unset the drupal fields
        if (strpos($key, 'field_') === 0) {
          unset($params[$key]);
        }
        if (substr($key, 0, 7) === 'custom_' && empty($entity->original->{$key}) && empty($value)) {
          unset($params[$key]);
        }
      }

      // special handling for relationships, empty case_id throws api exception
      if ($this->entityType == 'civicrm_relationship') {
        if (isset($params['case_id']) && ($params['case_id'] == '' || is_null($params['case_id']))) {
          unset($params['case_id']);
        }
      }

      $params['version'] = 3;
      $params['sequential'] = 1;

      if ($entity->is_new) {
        if (isset($params['id'])) {
          unset($params['id']);
        }
        $result = civicrm_api(substr($this->entityType, 8), 'create', $params);
      }
      else {
        $result = civicrm_api(substr($this->entityType, 8), 'create', $params);
      }

      if (isset($entity->is_new)) {
        $is_new = $entity->is_new;
        unset($entity->is_new);
      }
      unset($entity->is_new_revision);
      unset($entity->original);

      if (!civicrm_error($result)) {
        if ($is_new) {
          $entity->id = $result['values'][0]['id'];
          $this->invoke('insert', $entity);
        }
        else {
          $this->invoke('update', $entity);
        }

        foreach ($result['values'][0] as $key => $value) {
          $entity->key = $value;
        }
        return new CivicrmEntity(array_keys($result['values']), $this->entityType);
      }
      throw new Exception($result['error_message']);
    } catch (Exception $e) {
      watchdog_exception($this->entityType, $e);
      throw $e;
    }
  }

  public function delete($ids, DatabaseTransaction $transaction = NULL) {
    if (!civicrm_initialize()) {
      throw new Exception('civicrm inaccessible');
    }

    $entities = entity_load($this->entityType, $ids);
    if(count($entities)) {
      foreach ($ids as $id) {
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
        } catch (Exception $e) {
          watchdog_exception($this->entityType, $e);
          throw $e;
        }
      } //end foreach id
    }
  }

  public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
    $entity->content = $content;
    $build = parent::buildContent($entity, $view_mode, $langcode, $content);
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

    foreach ($temp_build as $key => $value) {
      $build[$key] = $value;
    }

    if (module_exists('ds')) {
      $layout = ds_get_layout($this->entityType, $this->entityType, $view_mode);
      if (!empty($layout['css']) && empty($layout['settings']['layout_disable_css'])) {
        // Add css file.
        if (isset($layout['module']) && $layout['module'] == 'panels') {
          $build['#attached']['css'][] = $layout['path'] . '/' . $layout['panels']['css'];
        }
        else {
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

    $fields = civicrm_api3($this->entityInfo['description'], 'getfields', array('action' => 'create'));
    if (!empty($fields['values'])) {
      $return_fields['return'] = array_keys($fields['values']);
    }

    // some fields are still not retrieved, for example the financial_type_id for Contribution get even though they are returned in the getfields list
    // and if you don't use any return conditions, they come through, but in that case custom fields don't come through
    // typical inconsistencies in the CiviCRm api that play havoc for automation

    // we really would prefer to use the 'create' action in the getfields list, because you get more....
    // but we need get to return all the field values for all the fields that are available for writing in the 'create' action

    // so we make 2 api calls, one with the 'return' property, and one without

    // get what we get with a API call with no 'return' property
    $civicrm_entities = civicrm_api3($this->entityInfo['description'], 'get', $condition);

    // get what we get by the 'return' property in the API call
    $condition['return'] = $return_fields['return'];
    $civicrm_entities2 = civicrm_api3($this->entityInfo['description'], 'get', $condition);

    // merge the two together
    $new_civicrm_entities = array();
    foreach ($civicrm_entities['values'] as $id => $entity) {
      $new_civicrm_entities[$id] = array_merge($entity, $civicrm_entities2['values'][$id]);
      // unset anything not in the return array (if you wanted to) but why?
      /*foreach($new_civicrm_entities[$id] as $key => $value){
        if(!in_array($key, $return_fields['return'])) {
          unset($new_civicrm_entities[$id][$key]);
        }
      }*/
    }
    // create the Drupal Entities
    if (count($new_civicrm_entities)) {
      foreach ($new_civicrm_entities as $id => $civicrm_entity) {
        // @TODO improve this casting.
        $entities[$id] = new CivicrmEntity($civicrm_entity, $this->entityType);
        if ($this->entityType == 'civicrm_contribution') {
          $entities[$id]->source = $entities[$id]->contribution_source;
        }
      }
      return $entities;
    }

    return $entities;
  }
}
