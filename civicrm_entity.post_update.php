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
