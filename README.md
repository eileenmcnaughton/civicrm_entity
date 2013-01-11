civicrm_entity
==============

This module is intended to expose CiviCRM as an entity within Drupal to allow 
rules integration, entity references, attachment views.

=========================================================================
Schema declaration
=========================================================================
This is being attempted WITHOUT declaring using the _schema hook because hook_schema
appears to only support the tables being in the same database. 

Note that it IS possible
to declare datetime fields in the hook_schema using the 'mysql_type' parameter
http://drupal.org/node/159605
Enum fields don't seem to be possible

I have over-riden classes & used a 'copy' of the drupal schema stuff with a civicrm schema
function but have limited to civicrm_relationship_type for now as this isn't 
declared within the civicrm_views module - whose implementation somewhat clashes

=========================================================================
Rules Integration
==========================================================================

=========================================================================
Views clash
=========================================================================
The civicrm_views module uses the views_data hook rather than views_data_alter() which
appears to clash

The entity module 
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
 
 