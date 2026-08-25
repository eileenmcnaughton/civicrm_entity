<?php

/**
 * @file
 * Post update functions for CiviCRM Entity.
 */

/**
 * Enable bundle support for CiviCRM Entity entity types.
 */
function civicrm_entity_post_update_entity_bundles(&$sandbox) {
  // An empty post_update hook triggers a cache rebuild, which ensures that
  // our new hooks and route changes are discovered.
}

/**
 * Rebuild Views cache for improved support.
 */
function civicrm_entity_post_update_views_data() {
  // Discover the new civicrm_views_query plugin.
  \Drupal::service('plugin.manager.views.query')->clearCachedDefinitions();
  // Rebuild CiviCRM Entity views data (database and query_id keys.)
  \Drupal::service('views.views_data')->clear();
}

/**
 * Enables integration submodules for existing Drupal 11 installations.
 */
function civicrm_entity_post_update_enable_integration_submodules() {
  $integration_requirements = [
    'civicrm_entity_vbo' => ['views_bulk_operations'],
    'civicrm_entity_rules' => ['rules', 'typed_data'],
    'civicrm_entity_search_api' => ['search_api'],
    'civicrm_entity_field_group' => ['civicrm_entity_bundles', 'field_group'],
    'civicrm_entity_ds' => ['civicrm_entity_bundles', 'ds'],
  ];

  $module_handler = \Drupal::moduleHandler();
  $extension_list = \Drupal::service('extension.list.module');
  $available_modules = $extension_list->reset()->getList();
  $module_installer = \Drupal::service('module_installer');
  $enabled = [];

  foreach ($integration_requirements as $integration => $requirements) {
    if ($module_handler->moduleExists($integration)) {
      continue;
    }
    foreach ($requirements as $requirement) {
      if (!$module_handler->moduleExists($requirement)) {
        continue 2;
      }
    }

    if (!isset($available_modules[$integration]) || !empty($available_modules[$integration]->info['core_incompatible'])) {
      continue;
    }
    if ($module_installer->install([$integration], TRUE)) {
      $enabled[] = $integration;
    }
  }

  return $enabled
    ? 'Enabled CiviCRM Entity integration modules: ' . implode(', ', $enabled) . '.'
    : NULL;
}
