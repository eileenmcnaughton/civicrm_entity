<?php
/**
 * @file
 * relationship_to_role_sync.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function relationship_to_role_sync_default_rules_configuration() {
  $items = array();
  $items['rules_relationship_created'] = entity_import('rules_config', '{ "rules_relationship_created" : {
      "LABEL" : "Relationship Created",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "civicrm_entity", "civicrm" ],
      "ON" : [ "civicrm_relationship_create" ],
      "IF" : [
        { "data_is" : {
            "data" : [ "civicrm-relationship:relationship-type-id" ],
            "value" : "8"
          }
        },
        { "data_is" : { "data" : [ "civicrm-relationship:is-active" ], "value" : "1" } }
      ],
      "DO" : [
        { "civicrm_entity_rules_action_load_create_user" : {
            "USING" : { "civicrm_contact" : [ "civicrm-relationship:contact-id-a-contact" ] },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created\\/ Loaded Drupal User" } }
          }
        },
        { "user_add_role" : { "account" : [ "civicrm-user" ], "roles" : { "value" : { "4" : "4" } } } }
      ]
    }
  }');
  return $items;
}
