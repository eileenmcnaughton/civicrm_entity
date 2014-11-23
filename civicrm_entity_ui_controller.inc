<?php

class CivicrmEntityUIController extends EntityContentUIController {

  public $overviewPagerLimit = 25;

  /**
   * Always use the same civicrm_entity_form
   * @return mixed
   */
  public function hook_forms() {
   $forms = parent::hook_forms();
    foreach ($this->entityInfo['bundles'] as $bundle => $bundle_info) {
      if (isset($bundle_info['admin'])) {
        $form_id = (!isset($bundle) || $bundle == $this->entityType) ? $this->entityType . '_form' : $this->entityType . '_edit_' . $bundle . '_form';
        $forms[$form_id] = array(
          'callback' => 'civicrm_entity_form',
        );
      }
    }
    return $forms;
  }
  public function overviewTable($conditions = array()) {
/*
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);

    // Add all conditions to query.
    foreach ($conditions as $key => $value) {
      $query->propertyCondition($key, $value);
    }

    if ($this->overviewPagerLimit) {
      $query->pager($this->overviewPagerLimit);
    }

    $results = $query->execute();

    $ids = isset($results[$this->entityType]) ? array_keys($results[$this->entityType]) : array();
    $entities = $ids ? entity_load($this->entityType, $ids) : array();
    ksort($entities);

    $rows = array();
    foreach ($entities as $entity) {
      $rows[] = $this->overviewTableRow($conditions, entity_id($this->entityType, $entity), $entity);
    }

    $render = array(
      '#theme' => 'table',
      '#header' => $this->overviewTableHeaders($conditions, $rows),
      '#rows' => $rows,
      '#empty' => t('None.'),
    );
    return $render;
*/
}

  public function hook_menu() {
   /* add menu items for all entities, will display with id as argument to url
    * for example goto <drupal_root>/civicrm_contact/1, in the example id =1
    */
   $civicrm_entities = _civicrm_entity_enabled_entities();
   foreach($civicrm_entities as $key => $value) {
     $dashkey = str_replace('_','-',$key);
     $titlename = ucwords(str_replace('_',' ', $key));
     $items[$dashkey.'/add'] = array(
       'title' => 'Create ' . $key,
       'page callback' => 'entity_ui_get_form',
       'page arguments' => array($key,array(),'create'),
       'access callback' => 'civicrm_entity_op_access',
       'access arguments' => array('create',$key),
       'type' => MENU_CALLBACK,
       
     );
      
     $items[$dashkey . '/' . '%'] = array(
      //'title' => 'Title',
        'title callback' => $key . '_page_title',
        'title arguments' => array(1),
        'page callback' => 'civicrm_entity_page_view',
        'page arguments' => array(1,$key),
        'access callback' => 'civicrm_entity_op_access',
        'access arguments' => array('view',$key),
       // 'type' => MENU_CALLBACK,
      );
     
     $items[$dashkey.'/%/view'] = array(
        'title' => 'View',
        'type' => MENU_DEFAULT_LOCAL_TASK,
        'weight' => -10,
      );
      
     $items[$dashkey.'/'.'%/edit'] = array(
       'title' => 'Edit',
       'page callback' => 'entity_ui_get_form',
       'page arguments' => array($key,1,'edit'),
       'access callback' => 'civicrm_entity_op_access',
       'access arguments' => array('edit',$key),
       'type' => MENU_LOCAL_TASK,
       'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
     );
     
     $items[$dashkey.'/'.'%/delete'] = array(
       'title' => 'Delete',
       'page callback' => 'entity_ui_get_form',
       'page arguments' => array($key,1,'delete'),
       'access callback' => 'civicrm_entity_op_access',
       'access arguments' => array('delete',$key),
       'type' => MENU_LOCAL_TASK,
       'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
       'weight' => 10
     );
     
     
    }

    return $items;
  }
}
