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
  \Drupal::getContainer()->get('plugin.manager.views.query')->clearCachedDefinitions();
  \Drupal::getContainer()->get('views.views_data')->clear();;
}
