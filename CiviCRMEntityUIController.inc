<?php

class CiviCRMEntityUIController extends EntityDefaultUIController {

  public function hook_forms() {
    $forms = parent::hook_forms();

    foreach ($this->entityInfo['bundles'] as $bundle => $bundle_info) {
      if (isset($bundle_info['admin'])) {

        $form_id = (!isset($bundle) || $bundle == $this->entityType) ? $this->entityType . '_form' : $this->entityType . '_edit_' . $bundle . '_form';

        $forms[$form_id] = array('callback' => 'civicrm_entity_form');
      }
    }
    return $forms;
  }


}
