<?php

/**
 * @file
 * Post update functions for CiviCRM Entity.
 */

/**
 * Implements hook_post_update_NAME().
 */
function civicrm_entity_post_update_entity_bundles(&$sandbox) {
  // An empty post_update hook triggers a cache rebuild, which ensures that
  // our new hooks and route changes are discovered.
}
