civicrm_entity
==============

This module is intended to expose CiviCRM as an entity within Drupal.

Focus so far has been on Rules - to the extent it is now possible to take action on 
a drupal user based on a civicrm action

e.g. - CiviCRM event is created => Organic Group for the event is created
     - CiviCRM Participant is created => participant is registered in the OG
     

Entities enabled (so far) event, participant, relationship, contact
 (Enabling more entities only requires adding them to an array 
 so caution rather than effort is limiting this)

Conditions usable = any data condition - e.g. data compare
 - eg. event_type_id = 'Conference'
 
Actions usable
 The only actions enabled here are 
  - load drupal user
  - create drupal user
  - load or create drupal user (this is the one I have user)
  Other actions are all normal drupal ones
  
  Note that some actions are showing - these don't work yet as there isn't a form
  to pass through criteria
 
 