<?php
/**
 * @file
 * civicrm_event_og_config_sample.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function civicrm_event_og_config_sample_default_rules_configuration() {
  $items = array();
  $items['rules_add_participant_to_og_event'] = entity_import('rules_config', '{ "rules_add_participant_to_og_event" : {
      "LABEL" : "add participant to og event",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "CiviCRM" ],
      "REQUIRES" : [ "rules", "og", "civicrm" ],
      "ON" : [ "civicrm_participant_create" ],
      "DO" : [
        { "entity_query" : {
            "USING" : {
              "type" : "node",
              "property" : "field_eventid",
              "value" : [ "civicrm-participant:event-id" ],
              "limit" : "1"
            },
            "PROVIDE" : { "entity_fetched" : { "event_og" : "OG for Event" } }
          }
        },
        { "civicrm_entity_rules_action_load_create_user" : {
            "USING" : { "civicrm_contact" : [ "civicrm-participant:contact-id-contact" ] },
            "PROVIDE" : { "civicrm_user" : { "civicrm_user" : "Created\\/ Loaded Drupal User" } }
          }
        },
        { "og_subcribe_user" : { "user" : [ "civicrm-user" ], "group" : [ "event-og:0" ] } },
        { "drupal_message" : { "message" : "[loaded-user:name] : [loaded-user:uid] has been added to the OG for this Event [event-og:0:nid]\\r\\n\\r\\n" } }
      ]
    }
  }');
  $items['rules_create_event_og'] = entity_import('rules_config', '{ "rules_create_event_og" : {
      "LABEL" : "create-event-og",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "CiviCRM" ],
      "REQUIRES" : [ "rules", "civicrm" ],
      "ON" : [ "civicrm_event_create" ],
      "IF" : [
        { "data_is" : { "data" : [ "civicrm-event:event-type-id" ], "value" : "1" } }
      ],
      "DO" : [
        { "entity_create" : {
            "USING" : {
              "type" : "node",
              "param_type" : "og_event",
              "param_title" : [ "civicrm-event:title" ],
              "param_author" : [ "site:current-user" ]
            },
            "PROVIDE" : { "entity_created" : { "entity_created" : "Created entity" } }
          }
        },
        { "data_set" : {
            "data" : [ "entity-created:field-eventid" ],
            "value" : [ "civicrm-event:id" ]
          }
        }
      ]
    }
  }');
  return $items;
}
