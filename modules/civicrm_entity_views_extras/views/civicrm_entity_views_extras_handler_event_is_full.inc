<?php

class civicrm_entity_views_extras_handler_event_is_full extends views_handler_field {
  function query() {}
  function render($values) {
    if (!civicrm_initialize()) {
      return;
    }
    try {
      $event = civicrm_api3('Event', 'getsingle', array('return.is_full' => 1, 'id' => $values->id));
      if (!empty($event['is_full'])) {
        return 1;
      }
      else {
        return 0;
      }
    } catch (CiviCRM_API3_Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }
  }
}
