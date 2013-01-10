<?php
/**
 * @file
 * civicrm_event_og_config_sample.features.inc
 */

/**
 * Implements hook_node_info().
 */
function civicrm_event_og_config_sample_node_info() {
  $items = array(
    'og_event' => array(
      'name' => t('CiviEvent Organic Group'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Event Title'),
      'help' => '',
    ),
  );
  return $items;
}
