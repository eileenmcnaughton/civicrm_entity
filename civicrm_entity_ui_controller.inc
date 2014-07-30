<?php

class CivicrmEntityUIController extends EntityContentUIController {

  public $overviewPagerLimit = 25;

  public function hook_forms() {
   /* $forms = parent::hook_forms();
    foreach ($this->entityInfo['bundles'] as $bundle => $bundle_info) {
      if (isset($bundle_info['admin'])) {
        $form_id = (!isset($bundle) || $bundle == $this->entityType) ? $this->entityType . '_form' : $this->entityType . '_edit_' . $bundle . '_form';
        $forms[$form_id] = array(
          'callback' => 'civicrm_entity_form',
        );
      }
    }
    dpm($forms);
    return $forms;
   */
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
}
