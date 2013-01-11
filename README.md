civicrm_entity
==============

This module is intended to expose CiviCRM as an entity within Drupal to allow 
rules integration, entity references, attachment views.

============================================================================
Status
============================================================================
This is a module intended to create civicrm entities as drupal entities. 
Intitial focus was on Rules & there is reasonable progress there. (see below)

I took one entity - civicrm_relationship_type a bit further and explored exposing
it as an entity reference. I got the entity reference field enterable & savable, but
on the node display it doesn't yet display

Some consideration needs to be given to security & possibly performance going forwards
I haven't added a permissions to access CiviCRM objects in rules (yet) which is a gap

Also, the post hook is called for each enabled entity & then the rules hook - the rules hook
is probably pretty light so it may not matter much - however I have only enabled 
4 entities so far until further thought as to whether they should be configurable

=================================================================================
Features
================================================================================
I have packaged in 2 features showing rules in use. One creates OGs from events & subscribes
participants to them. The other adds people to drupal roles based on CiviCRM relationships

It takes a bit of getting used to the Rules config so they are mostly to 'show how
it is done'

================================================================================
Crap code
================================================================================
I definitely went down a few rabbit holes - in particular around the schema stuff &
some of that code is still in there - I'm not sure without more investigation which bits 
can go. In particular I'm not sure if I needed all the schema over-ride stuff in the end
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
 
 
=========================================================================
Views clash
=========================================================================
The civicrm_views module uses the views_data hook rather than views_data_alter() which
appears to clash. I have deliberately only tested extending civicrm_entity for the 
relationship_type entity which is not declared by views_data hook


=============================================================================
Fieldability & Display
=============================================================================
I haven't gone far down this apart from looking at creating bundles but it seems like the idea 
of having a CiviCRM entity that has a 'life' in drupal and can have drupal fields attached
to it may hold possibilities