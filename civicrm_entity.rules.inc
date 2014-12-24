<?php

/**
 * @file
 * Implement Drupal Rules integration for CiviCRM
 */

/**
 * Implements of hook_rules_data_type_info().
 */
function civicrm_entity_rules_data_info() {
  if (!civicrm_initialize(TRUE)) {
    return;
  }
  module_load_include('module', 'civicrm_entity', 'civicrm_entity');

  $info = array();
  $entities = _civicrm_entity_enabled_entities();

  foreach ($entities as $drupal_entity => $civicrm_entity) {
    $info[$drupal_entity] = array(
      'label' => t('CiviCRM @entity', array('@entity' => $civicrm_entity)),
      // parent => 'civicrm_entity',
      'wrap' => TRUE,
      'group' => 'CiviCRM',
      // 'ui class' => 'RulesDataUIEntityExportable',
      'module' => 'civicrm',
    );
    $info[$drupal_entity]['property_info'] = _civicrm_entity_getproperties($civicrm_entity);
    // I don't think these are being 'kept'.
    $fields = civicrm_api($civicrm_entity, 'getfields', array(
      'version' => 3,
      'action' => 'create'
    ));
    foreach ($fields['values'] as $fieldname => $field_specs) {
      if (!empty($field_specs['type']) && $field_specs['type'] == 1) {
        // Type is empty for custom fields - we should sort that out
        // but skipping for now.
        if (CRM_Utils_Array::Value('FKClassName', $field_specs)) {
          $fks = _civicrm_entity_chained_fks();
          if (array_key_exists($field_specs['FKClassName'], $fks)) {
            $fks_entity = $fks[$field_specs['FKClassName']];
            $info[$drupal_entity]['property_info'][$fieldname] = array(
              'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
              'type' => 'civicrm_' . $fks_entity,
            );
          }
        }

        if ($fieldname == 'entity_id') {
          $fks_entity = 'contact';
          $info[$drupal_entity]['property_info'][$fieldname] = array(
            'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
            'type' => 'civicrm_' . $fks_entity,
          );
        }

        $info[$drupal_entity]['property_info'][$fieldname] = array(
          'label' => empty($field_specs['title']) ? 'label not defined in CiviCRM schema' : $field_specs['title'],
          'type' => 'integer',
        );
        if (!empty($field_specs['options'])) {
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
 * Implements hook_rules_event_info().
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
      $create_msg = t('Created !entity', array('!entity' => $entity_name));
      $events[$entity . '_create'] = array(
        'label' => t("!entity has been created", array('!entity' => $entity_name)),
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'variables' => civicrm_entity_rules_events_variables($create_msg, $entity, $civicrm_entity),
      );
      $update_msg = t('Updated !entity', array('!entity' => $entity_name));
      $events[$entity . '_edit'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been updated", array('!entity' => $entity_name)),
        'variables' => civicrm_entity_rules_events_variables($update_msg, $entity),
      );
      $view_msg = t('Viewed !entity', array('!entity' => $entity_name));
      $events[$entity . '_view'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been viewed", array('!entity' => $entity_name)),
        'variables' => civicrm_entity_rules_events_variables($view_msg, $entity),
      );
      $delete_msg = t('Deleted !entity', array('!entity' => $entity_name));
      $events[$entity . '_delete'] = array(
        'group' => $entity_name,
        'module' => 'civicrm',
        'access_callback' => '_civicrm_entity_rules_access',
        'label' => t("!entity has been deleted", array('!entity' => $entity_name)),
        'variables' => civicrm_entity_rules_events_variables($delete_msg, $entity),
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
  $conditions = array(
    'civicrm_entity_user_exists' => array(
      'label' => t('Drupal User Account exists for Contact'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact',
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Drupal User'),
          'type' => 'user',
        ),
      ),
      'group' => t('CiviCRM'),
    ),
    'civicrm_entity_user_creatable' => array(
      'label' => t('Drupal User Account can be created for user (this condition creates the user)'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact',
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
          'type' => 'user',
        ),
      ),
      'group' => t('CiviCRM'),
    ),
    'civicrm_entity_user_exists_or_creatable' => array(
      'label' => t('Drupal User Account exists or can be created for user'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact',
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Drupal User'),
          'type' => 'user',
        ),
      ),
      'group' => t('CiviCRM'),
    ),
    'civicrm_entity_query' => array(
      'label' => t('Can Fetch CiviCRM entity by property'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'type' => array(
          'type' => 'text',
          'label' => t('CiviCRM Entity type'),
          'options list' => '_civicrm_entity_enabled_entities',
          'description' => t('Specifies the type of CiviCRM entity that should be fetched.'),
          'restriction' => 'input',
        ),
        'property' => array(
          'type' => 'text',
          'label' => t('Property'),
          'description' => t('The property by which the entity is to be selected.'),
          'restriction' => 'input',
        ),
        'value' => array(
          'type' => 'unknown',
          'label' => t('Value'),
          'description' => t('The property value of the entity to be fetched.'),
        ),
        'limit' => array(
          'type' => 'integer',
          'label' => t('Limit result count'),
          'description' => t('Limit the maximum number of fetched entities.'),
          'optional' => TRUE,
          'default value' => '10',
        ),
      ),
      'group' => t('CiviCRM'),
      'callbacks' => array(
        'form_alter' => 'rules_action_type_form_alter',
      ),
      'provides' => array(
        'entity_fetched' => array('type' => 'list', 'label' => t('Fetched CiviCRM entity list (being ignored?)')),
      ),
      'base' => 'civicrm_entity_query',
    ),
  );
  //@todo add phone as an entity & add it here, also - I had to remove address as II was less sure about the right approach as I went along

  foreach (array('email') as $address_entity) {
    $conditions['civicrm_entity_contact_has' . $address_entity] = array(
      'group' => t('CiviCRM'),
      'label' => t('CiviCRM Contact has ' . ucfirst($address_entity)),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'contact' => array(
          'type' => 'civicrm_contact',
          'label' => t('CiviCRM Contact'),
          'description' => t('The CiviCRM Contact to fetch for'),
        ),
        'location' => array(
          'type' => 'integer',
          'label' => t('Location'),
          'description' => t('The property by which the entity is to be selected.'),
          'options list' => 'civicrm_entity_get_locations',
        ),
      ),
      'provides' => array(
        'created_' . $address_entity => array(
          'type' => 'civicrm_' . $address_entity,
          'label' => t('CiviCRM ' . ucfirst($address_entity)),
        ),
      ),
      'base' => 'civicrm_entity_contact_has_location_element_' . $address_entity,
    );
  }

  return $conditions;
}

/**
 * Implements hook_rules_action_info().
 *
 * @see http://drupalcontrib.org/api/drupal/contributions!rules!rules.api.php/function/hook_rules_action_info/7
 */
function civicrm_entity_rules_action_info() {
  $actions = array(
    'civicrm_entity_action_load_user' => array(
      'label' => t('Load Drupal User Account'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact',
        ),
      ),
      'provides' => array(
        'civicrm_user' => array(
          'label' => t('Loaded Drupal User'),
          'type' => 'user',
        ),
      ),
      'group' => t('CiviCRM'),
      'access callback' => '_civicrm_entity_rules_access',
      'base' => 'civicrm_entity_action_load_user',
    ),
    'civicrm_entity_action_create_user' => array(
      'label' => t('Create Linked Drupal User Account'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'civicrm_contact' => array(
          'label' => t('CiviCRM Contact'),
          'type' => 'civicrm_contact',
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
          'type' => 'user',
        ),
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
          'type' => 'civicrm_contact',
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
          'type' => 'user',
        )
      ),
      'group' => t('CiviCRM'),
      'base' => 'civicrm_entity_action_load_create_user',
    ),
    'civicrm_entity_query' => array(
      'label' => t('Fetch CiviCRM entity by property'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'type' => array(
          'type' => 'text',
          'label' => t('CiviCRM Entity type'),
          'options list' => '_civicrm_entity_enabled_entities',
          'description' => t('Specifies the type of CiviCRM entity that should be fetched.'),
          'restriction' => 'input',
        ),
        'property' => array(
          'type' => 'text',
          'label' => t('Property'),
          'description' => t('The property by which the entity is to be selected.'),
          'restriction' => 'input',
        ),
        'value' => array(
          'type' => 'unknown',
          'label' => t('Value'),
          'description' => t('The property value of the entity to be fetched.'),
        ),
        'limit' => array(
          'type' => 'integer',
          'label' => t('Limit result count'),
          'description' => t('Limit the maximum number of fetched entities.'),
          'optional' => TRUE,
          'default value' => '10',
        ),
      ),
      'group' => t('CiviCRM'),
      'callbacks' => array(
        'form_alter' => 'rules_action_type_form_alter',
      ),
      'provides' => array(
        'entity_fetched' => array('type' => 'list', 'label' => t('Fetched CiviCRM entity list')),
      ),
      'base' => 'civicrm_entity_query',
    ),
    'civicrm_entity_action_load_create_contact' => array(
      'label' => t('Create or Load Linked CiviCRM Contact'),
      'module' => 'civicrm',
      'access_callback' => '_civicrm_entity_rules_access',
      'parameter' => array(
        'user' => array(
          'label' => t('Drupal User'),
          'type' => 'user',
        ),
        'email' => array(
          'label' => t('email'),
          'type' => 'email',
          'optional' => TRUE,
        )
      ),
      'provides' => array(
        'civicrm_contact' => array(
          'label' => t('Created or Loaded CiviCRM Contact'),
          'type' => 'civicrm_contact',
        )
      ),
      'group' => t('CiviCRM'),
      'base' => 'civicrm_entity_action_load_create_contact',
    ),
  );

  $actions['civicrm_entity_contact_get_email'] = array(
    'group' => t('CiviCRM'),
    'label' => t('CiviCRM Contact get Email'),
    'module' => 'civicrm',
    'access_callback' => '_civicrm_entity_rules_access',
    'parameter' => array(
      'contact' => array(
        'type' => 'civicrm_contact',
        'label' => t('CiviCRM Contact'),
        'description' => t('The CiviCRM Contact to fetch for'),
      ),
      'location' => array(
        'type' => 'integer',
        'label' => t('Location'),
        'description' => t('The property by which the entity is to be selected.'),
        'options list' => 'civicrm_entity_get_locations',
      ),
    ),
    'provides' => array(
      'fetched_email' => array(
        'type' => 'text',
        'label' => t('Email'),
      ),
    ),
    'base' => 'civicrm_entity_contact_get_email',
  );

  $actions['civicrm_entity_contact_send_email'] = array(
    'group' => t('CiviCRM'),
    'label' => t('CiviCRM Contact Send Email'),
    'module' => 'civicrm',
    'access_callback' => '_civicrm_entity_rules_access',
    'parameter' => array(
      'contact' => array(
        'type' => 'civicrm_contact',
        'label' => t('CiviCRM Contact'),
        'description' => t('The CiviCRM Contact to email'),
      ),
      'subject' => array(
        'type' => 'text',
        'label' => t('Subject'),
        'description' => t("The mail\'s subject."),
      ),
      'message_text' => array(
        'type' => 'text',
        'label' => t('Message'),
        'description' => t("The mail\'s message body - text version."),
        'optional' => TRUE,
      ),
      'message_html' => array(
        'type' => 'text',
        'label' => t('Message'),
        'description' => t("The mail\'s message body - html version."),
        'optional' => TRUE,
      ),
      'from' => array(
        'type' => 'text',
        'label' => t('From'),
        'description' => t("The mail's from address. Leave it empty to use the site-wide configured address."),
        'optional' => TRUE,
      ),
    ),
    'base' => 'civicrm_entity_contact_send_email',
  );

  return $actions;
}

/**
 * User access function.
 *
 * @param String $type
 *   Ignored.
 *
 * @param String $name
 *   Ignored.
 *
 * @return bool
 */
function civicrm_entity_rules_access($type, $name) {
  return user_access('civicrm_entity.rules.administer');
}

/**
 * Please document this function.
 *
 * @param $label
 * @param $type
 * @param null $civicrm_entity
 *
 * @return array
 */
function civicrm_entity_rules_events_variables($label, $type, $civicrm_entity = NULL) {
  $vars = array();
  $default = array(
    $type => array(
      'type' => $type,
      'label' => $label,
      'description' => t('CiviCRM Entity - @label', array('@label' => $label)),
    ),
  );

  return $default;
}

/**
 * Add some access params! @TODO.
 */
function _civicrm_entity_rules_access() {
  return TRUE;
}

/**
 * Return options list.
 * @param $fieldname
 * @param $info
 * @param $arg3
 * @return
*/
function _civicrm_entity_rules_attach_options($fieldname, $info, $arg3) {
  return $info['property defaults']['options list'];
}
