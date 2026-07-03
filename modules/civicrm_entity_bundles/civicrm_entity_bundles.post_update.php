<?php

/**
 * @file
 * Post update functions for CiviCRM Entity Bundles.
 */

/**
 * Enable bundle support for CiviCRM Entity entity types.
 */
function civicrm_entity_bundles_post_update_entity_bundles(&$sandbox) {
  // An empty post_update hook triggers a cache rebuild, which ensures that
  // new hooks and route changes from this module are discovered.
}
