<?php

/**
 * @file
 * Api information provided by CiviCRM Entity module
 */

/**
 * @mainpage
 * API Reference Documentation for the CiviCRM Entity module.
 *
 */


/**
 * Hook to alter (or add) CiviCRM entities that are recognized and integrated by CiviCRM Entity
 * The API supports the following keys
 *
 * -civicrm entity name: the CiviCRM API name of the entity
 * -label property: the property of the CiviCRM API entity to use for the Drupal entity label
 * -permissions: (optional) array
 *   -view: (optional) array of permissions necessary to view Drupal entity (defaults to 'administer CiviCRM' if not provided)
 *   -edit: (optional) array of permissions necessary to edit Drupal entity (defaults to 'administer CiviCRM' if not provided)
 *   -update: (optional) array of permissions necessary to update Drupal entity (defaults to 'administer CiviCRM' if not provided)
 *   -create: (optional) array of permissions necessary to create Drupal entity (defaults to 'administer CiviCRM' if not provided)
 *   -delete: (optional) array of permissions necessary to delete Drupal entity (defaults to 'administer CiviCRM' if not provided)
 * -theme: (optional) array
 *   -template: (optional) Name of template file (without the .tpl.php extension)
 *   -path: (optional) Path to template file
 * -display suite: (optional) array
 *   -link fields: (optional) array of arrays of link field properties
 *     -link_field: Field that will be rendered as a link
 *     -target: drupal entity machine name of the entity to link to
 *  -option fields: (optional) array of fields to apply option field formatters to
 *  -boolean fields: (optional) array of fields to apply yes/no, true/false field formatters to
 *
 * @param $civicrm_entity_info
 */
function hook_civicrm_entity_supported_info(&$civicrm_entity_info) {
  $civicrm_entity_info['civicrm_phone'] = array(
    'civicrm entity name' => 'phone',
    'label property' => 'phone',
    'permissions' => array(
      'view' => array('view all contacts'),
      'edit' => array('edit all contacts'),
      'update' => array('edit all contacts'),
      'create' => array('edit all contacts'),
      'delete' => array('delete contacts'),
    ),
    'theme' => array(
      'template' => 'civicrm-phone',
      'path' => drupal_get_path('module', 'civicrm_entity') . '/templates'
    ),
    'display suite' => array(
      'link fields' => array(
        array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
        ),
      ),
      'option fields' => array('location_type_id', 'mobile_provider_id', 'phone_type_id'),
      'boolean fields' => array('is_primary', 'is_billing',),
    ),
  );
}
