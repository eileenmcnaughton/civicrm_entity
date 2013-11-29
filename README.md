CiviCRM Entity
==============

This module is available from [Drupal.org Contrib](https://drupal.org/project/civicrm_entity)

This module is intended to expose CiviCRM as an entity within Drupal to allow 
rules integration, entity references, node to civi-views etc.


## Status

Intitial focus was on Rules & there is reasonable progress there. (see below)

The areas I have looked at so far are mostly rules integration, entity reference fields

Some consideration needs to be given to security & possibly performance going forwards
I haven't added a permissions to access CiviCRM objects in rules (yet) which is a gap

Also, the post hook is called for each enabled entity & then the rules hook - the rules hook
is probably pretty light so it may not matter much - however I have only enabled 
4 entities so far until further thought as to whether they should be configurable. 

## Features

I have packaged in 2 features showing rules in use. One creates OGs from events & subscribes
participants to them. The other adds people to drupal roles based on CiviCRM relationships

It takes a bit of getting used to the Rules config so they are mostly to 'show how
it is done'

## Schema declaration

The schema hook really expects your fields to be in the same database so I have 
used hook_schema_alter


Note that it IS possible to declare datetime fields in the hook_schema using the 'mysql_type' parameter
http://drupal.org/node/159605

## Rules Integration

The entity module focus so far has been on Rules - to the extent it is now possible to take action on 
a drupal user based on a civicrm action

e.g. - CiviCRM event is created => Organic Group for the event is created
     - CiviCRM Participant is created => participant is registered in the OG
     
### Entities enabled

* event
* participant
* relationship
* contact

(Enabling more entities only requires adding them to an array  so caution rather than effort is limiting this)

Conditions usable = any data condition - e.g. data compare
 - eg. event_type_id = 'Conference'
 
### Actions usable

The actions enabled here are 

* load drupal user
* create drupal user
* load or create drupal user (this is the one I have user)

Other actions are all normal drupal ones
  
Note that some actions are showing - these don't work yet as there isn't a form to pass through criteria

## Views

The civicrm_views module uses the views_data hook rather than views_data_alter() which
appears to clash. I have been testing changing this.

The civicrm_entity approach would probably eventually replace most of the views integration
code with only a few items that need to be handcoded being in the views code.

## Fieldability & Display

I haven't gone far down this apart from looking at creating bundles but it seems like the idea 
of having a CiviCRM entity that has a 'life' in drupal and can have drupal fields attached
to it may hold possibilities

## Performance

One issue is considering whether we should limit the fields that get loaded when an entity is loaded
I believe the drupal bundles concept may be an approach to define a minimum set of fields, a medium set
and a comprehensive one. However, this would need to be defined somehow? Preferably not ad hoc.

