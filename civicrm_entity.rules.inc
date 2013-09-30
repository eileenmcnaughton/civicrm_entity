<?php

/**
 * @file
 * Implement Drupal Rules integration for CiviCRM
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 */

/**
 * Implements of hook_rules_data_type_info().
 */
function civicrm_entity_rules_data_info() {
  if (! civicrm_initialize(TRUE)) {
    return;
  }

  $info = array();
  $entities = _civicrm_entity_enabled_entities();

  foreach ($entities as $drupal_entity => $civicrm_entity) {
    $info[$drupal_entity] = array(
      'label' => t('CiviCRM' . $civicrm_entity),
      //parent => 'civicrm_entity',
      'wrap' => TRUE,
      'group' => 'CiviCRM',
      // 'ui class' => 'RulesDataUIEntityExportable',
      'module' => 'civicrm'
    );
    $info[$drupal_entity]['property_info'] = _civicrm_entity_getproperties($civicrm_entity);
    // I don't think these are being 'kept'.
    $fields = civicrm_api($civicrm_entity, 'getfields', array(
      'version' => 3,
      'action' => 'create'
    ));
    foreach ($fields['values'] as $fieldname => $field_specs) {
      if (! empty($field_specs['type']) && $field_specs['type'] == 1) {
        // Type is empty for custom fields - we should sort that out
        // but skipping for now.
        if (CRM_Utils_Array::value('FKClassName', $field_specs)) {
          $fks = _civicrm_entity_chained_fks();
          if (array_key_exists($field_specs['FKClassName'], $fks)) {
            $fksEntity = $fks[$field_specs['FKClassName']];
            $info[$drupal_entity]['property_info'][$fieldname] = array(
              'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
              'type' => 'civicrm_' . $fksEntity
            );
          }
        }

        if ($fieldname == 'entity_id') {
          $fksEntity = 'contact';
          $info[$drupal_entity]['property_info'][$fieldname] = array(
            'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
            'type' => 'civicrm_' . $fksEntity
         );
        }

        $info[$drupal_entity]['property_info'][$fieldname] = array(
          'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
          'type' => 'integer'
        );
        if (! empty($field_specs['options'])) {
          $info[$drupal_entity]['property_info'][$fieldname]['type'] = 'list<integer>';
          $info[$drupal_entity]['property_info'][$fieldname]['options list'] = '_civicrm_entity_rules_attach_options';
          $info[$drupal_entity]['property_info'][$fieldname]['options data'] = $field_specs['options'];
        }
      }
    }
  }

  return $info;
}

/**
 * Implementation of hook_rules_event_info().
 */
function civicrm_entity_rules_event_info() {
  $events = array();
  $valid_objects = _civicrm_entity_enabled_entities();
  // array('Event' => 'civicrm_event');
  if (is_array($valid_objects)) {
    foreach ($valid_objects as $entity => $civicrm_entity) {
      $entity_name = 'CiviCRM ' . ucwords(str_replace('_', ' ', $civicrm_entity));
      // @TODO consider building the entity name into the argument
      // rather than calling the same argument for each.
      $events[$entity . '_create'] = array(
        'label' => t("!entity has been created", array(
          '!entity' => $entity_name
        )),
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'variables' => civicrm_entity_rules_events_variables(t('Created !entity', array(
          '!entity' => $entity_name
        )), $entity, $civicrm_entity)
      );
      $events[$entity . '_edit'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been updated", array(
          '!entity' => $entity_name
        )),
        'variables' => civicrm_entity_rules_events_variables(t('Updated !entity', array(
          '!entity' => $entity_name
        )), $entity)
      );
      $events[$entity . '_view'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been viewed", array(
          '!entity' => $entity_name
        )),
        'variables' => civicrm_entity_rules_events_variables(t('Viewed !entity', array(
          '!entity' => $entity_name
        )), $entity)
      );
      $events[$entity . '_delete'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been deleted", array(
          '!entity' => $entity_name
        )),
        'variables' => civicrm_entity_rules_events_variables(t('Deleted !entity', array(
          '!entity' => $entity_name
        )), $entity)
      );
    }
  }
  return $events;
}

/**
 * Implements hook_rules_condition_info().
 *
 * @TODO I don't think the provides array is supported for conditions?
 * Remove.
 */
function civicrm_entity_rules_condition_info() {
  return array(
    'civicrm_entity_user_exists' => array(
      'label' => t('Drupal User Account exists for Contact'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        )
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM')
    ),
    'civicrm_entity_user_creatable' => array(
      'label' => t('Drupal User Account can be created for user (this condition creates the user)'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        ),
        'is_active' => array(
          'label' => t('Activate Account'),
          'type' => 'boolean',
          'default' => 1,
        ),
        'notify' => array(
          'label' => t('Send account notification email'),
          'type' => 'boolean',
          'default' => 1,
        ),
        'signin' => array(
          'label' => t('Instant signin'),
          'type' => 'boolean',
          'default' => FALSE,
          'description' => t('Automatically log in as the created user.'),
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM')
    ),
    'civicrm_entity_user_exists_or_creatable' => array(
      'label' => t('Drupal User Account exists or can be created for user'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        )
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM')
    ),
  );
}

/**
 * Implements hookrules_action_info().
 *
 * @see http://drupalcontrib.org/api/drupal/contributions!rules!rules.api.php/function/hook_rules_action_info/7
 */
function civicrm_entity_rules_action_info() {
  return array(
    'civicrm_entity_action_load_user' => array(
      'label' => t('Load Drupal User Account'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Loaded Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM'),
      'access callback' => 'payment_rules_access',
      'base' => 'civicrm_entity_action_load_user'
    ),
    'civicrm_entity_action_create_user' => array(
      'label' => t('Create Linked Drupal User Account'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        ),
        'is_active' => array(
          'label' => t('Activate Account'),
          'type' => 'boolean',
          'default' => 1,
        ),
        'notify' => array(
          'label' => t('Send account notification email'),
          'type' => 'boolean',
          'default' => 1,
        ),
        'signin' => array(
          'label' => t('Instant signin'),
          'type' => 'boolean',
          'default' => FALSE,
          'description' => t('Automatically log in as the created user.'),
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Created Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM'),
      'base' => 'civicrm_entity_action_create_user'
    ),
    'civicrm_entity_action_load_create_user' => array(
      'label' => t('Create or Load Linked Drupal User Account'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact'
        ),
        'is_active' => array(
          'label' => t('Activate Account'),
          'type' => 'boolean',
          'default' => 1,
        ),
        'notify' => array(
          'label' => t('Send account notification email'),
          'type' => 'boolean',
          'default' => 1,
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Created or Loaded Drupal User'),
          'type' => 'user'
        )
      ),
      'group' => t('CiviCRM'),
      'base' => 'civicrm_entity_action_load_create_user'
    )
  );
}

/**
 * User access function
 *
 * @param String $type
 * @param String $name
 */
function civicrm_entity_rules_access($type, $name) {
  return user_access('civicrm_entity.rules.administer');
}

/**
 *
 */
function civicrm_entity_rules_events_variables($label, $type, $civicrm_entity = NULL) {
  $vars = array();
  /*
   * this was adding other entities to args - but seems to expect them rather than chain them
  if ($civicrm_entity && civicrm_initialize(True)){
    $fields = civicrm_api($civicrm_entity, 'getfields', array('version' => 3));
    $fields = $fields['values'];
    // only doing for contacts right now
    foreach ($fields as $field => $field_specs){
      if (CRM_Utils_Array::value('FKClassName', $field_specs) =='CRM_Contact_DAO_Contact'){
        $vars[$field . '_contact'] = array(
          'type' => 'civicrm_contact',
          'label' => $field_specs['title'] . " Contact",
        );
      }
    }
    if ($civicrm_entity == 'contact'){
      $vars['civi_user'] = array(
        'type' => 'user',
        'label' => $field_specs['title'] . " User",
      );
    }
  }
*/
  $default = array(
    $type => array(
      'type' => $type,
      'label' => $label,
      'description' => "CiviCRM Entity - $label"
    )
  );

  return $default;
}

/**
 * @TODO Add some access params!
 */
function _civicrm_entity_rules_access() {
  return TRUE;
}

/**
 * Return options list.
 */
function _civicrm_entity_rules_attach_options($fieldname, $info, $arg3) {
  return $info['property defaults']['options list'];
}
