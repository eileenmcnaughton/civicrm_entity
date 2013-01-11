<?php
/**
 * @file
 * relationship_to_role_sync.features.user_role.inc
 */

/**
 * Implements hook_user_default_roles().
 */
function relationship_to_role_sync_user_default_roles() {
  $roles = array();

  // Exported role: Case Managers.
  $roles['Case Managers'] = array(
    'name' => 'Case Managers',
    'weight' => '3',
  );

  return $roles;
}
