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
  $items['rules_relationship_activated'] = entity_import('rules_config', '{ "rules_relationship_activated" : {
      "LABEL" : "Relationship Activated",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "CiviCRM", "CiviCRM Relationship" ],
      "REQUIRES" : [ "rules", "civicrm" ],
      "ON" : [ "civicrm_relationship_edit" ],
      "IF" : [
        { "data_is" : {
            "data" : [ "civicrm-relationship:relationship-type-id" ],
            "value" : "8"
          }
        },
        { "data_is" : { "data" : [ "civicrm-relationship:is-active" ], "value" : "1" } }
      ],
      "DO" : [
        { "civicrm_entity_action_load_create_user" : {
            "USING" : {
              "civicrm_contact" : [ "civicrm-relationship:contact-id-b-contact" ],
              "is_active" : 1,
              "notify" : 1
            },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created\\/ Loaded Drupal User" } }
          }
        },
        { "user_add_role" : { "account" : [ "civicrm-user" ], "roles" : { "value" : { "4" : "4" } } } },
        { "drupal_message" : { "message" : "[civicrm-user:uid] has had role added" } }
      ]
    }
  }');
  $items['rules_relationship_created'] = entity_import('rules_config', '{ "rules_relationship_created" : {
      "LABEL" : "Relationship Created",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "civicrm" ],
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
        { "civicrm_entity_action_load_create_user" : {
            "USING" : {
              "civicrm_contact" : [ "civicrm-relationship:contact-id-b-contact" ],
              "is_active" : 1,
              "notify" : 1
            },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created\\/ Loaded Drupal User" } }
          }
        },
        { "user_add_role" : { "account" : [ "civicrm-user" ], "roles" : { "value" : { "4" : "4" } } } }
      ]
    }
  }');
  $items['rules_relationship_de_activated_'] = entity_import('rules_config', '{ "rules_relationship_de_activated_" : {
      "LABEL" : "Relationship De-Activated ",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "CiviCRM", "CiviCRM Relationship" ],
      "REQUIRES" : [ "rules", "civicrm" ],
      "ON" : [ "civicrm_relationship_edit" ],
      "IF" : [
        { "data_is" : {
            "data" : [ "civicrm-relationship:relationship-type-id" ],
            "value" : "8"
          }
        },
        { "data_is" : { "data" : [ "civicrm-relationship:is-active" ], "value" : "0" } }
      ],
      "DO" : [
        { "civicrm_entity_action_load_create_user" : {
            "USING" : {
              "civicrm_contact" : [ "civicrm-relationship:contact-id-b-contact" ],
              "is_active" : 1,
              "notify" : 1
            },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created\\/ Loaded Drupal User" } }
          }
        },
        { "drupal_message" : { "message" : "[civicrm-user:uid] has had CMS Editor role removed" } },
        { "user_remove_role" : { "account" : [ "civicrm-user" ], "roles" : { "value" : { "4" : "4" } } } }
      ]
    }
  }');
  $items['rules_relationship_deleted'] = entity_import('rules_config', '{ "rules_relationship_deleted" : {
      "LABEL" : "Relationship Deleted",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "CiviCRM", "CiviCRM Relationship" ],
      "REQUIRES" : [ "rules", "civicrm" ],
      "ON" : [ "civicrm_relationship_delete" ],
      "IF" : [
        { "data_is" : {
            "data" : [ "civicrm-relationship:relationship-type-id" ],
            "value" : "8"
          }
        }
      ],
      "DO" : [
        { "civicrm_entity_action_load_create_user" : {
            "USING" : {
              "civicrm_contact" : [ "civicrm-relationship:contact-id-b-contact" ],
              "is_active" : 0,
              "notify" : 0
            },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created or Loaded Drupal User" } }
          }
        },
        { "user_remove_role" : { "account" : [ "civicrm-user" ], "roles" : { "value" : { "4" : "4" } } } }
      ]
    }
  }');
  return $items;
}
