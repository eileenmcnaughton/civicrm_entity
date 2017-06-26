<?php

class civicrm_entity_views_extras_handler_event_is_full extends views_handler_field {
	function render($values) {
        $event = civicrm_api3('Event', 'getsingle', array('return.is_full' => 1, 'id' => $values->id));
        if (!empty($event['is_full'])) {
            return 1;
        }
        else {
            return 0;
        }
	}
}