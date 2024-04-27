<?php

/**
 * @file
 * CiviCRM Entity Views related hooks and functions.
 */

use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\Markup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 *
 * @note
 * @note Copy and paste of views_views_data_alter to support our storage check.
 *
 * @see views_views_data()
 * @see views_views_data_alter()
 */
function civicrm_entity_views_data_alter(&$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('field_storage_config')) {
    return;
  }

  // @codingStandardsIgnoreStart
  // Start: views_views_data() snippet.

  // Field modules can implement hook_field_views_data() to override the default
  // behavior for adding fields.
  $module_handler = \Drupal::moduleHandler();

  /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
  foreach ($entity_type_manager->getStorage('field_storage_config')->loadMultiple() as $field_storage) {
    if (_civicrm_entity_field_get_entity_type_storage($field_storage)) {
      $result = (array) $module_handler->invoke($field_storage->getTypeProvider(), 'field_views_data', [$field_storage]);
      if (empty($result)) {
        $result = civicrm_entity_field_default_views_data($field_storage);
      }
      $module_handler->alter('field_views_data', $result, $field_storage);

      if (is_array($result)) {
        $data = NestedArray::mergeDeep($result, $data);
      }
    }
  }
  // End: views_views_data() snippet.
  // @codingStandardsIgnoreEnd

  // @codingStandardsIgnoreStart
  // Start: views_views_data_alter() snippet.

  /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
  foreach ($entity_type_manager->getStorage('field_storage_config')->loadMultiple() as $field_storage) {
    if (_civicrm_entity_field_get_entity_type_storage($field_storage)) {
      $function = $field_storage->getTypeProvider() . '_field_views_data_views_data_alter';
      if (function_exists($function)) {
        $function($data, $field_storage);
      }
    }
  }
  // End: views_views_data_alter() snippet.
  // @codingStandardsIgnoreEnd

  $civicrm_mailing_events = [
    'civicrm_mailing_event_bounce' => [
      'title' => t('Bounces'),
      'description' => t('Total count of Bounces.'),
      'bao' => 'CRM_Mailing_Event_BAO_Bounce',
    ],
    'civicrm_mailing_event_confirm' => [
      'title' => t('Confirm'),
      'description' => t('Total count of Confirmations.'),
      'bao' => 'CRM_Mailing_Event_BAO_Confirm',
    ],
    'civicrm_mailing_event_delivered' => [
      'title' => t('Delivered'),
      'description' => t('Total count of Delivered.'),
      'bao' => 'CRM_Mailing_Event_BAO_Delivered',
    ],
    'civicrm_mailing_event_forward' => [
      'title' => t('Forward'),
      'description' => t('Total count of Forwarded.'),
      'bao' => 'CRM_Mailing_Event_BAO_Forward',
    ],
    'civicrm_mailing_event_opened' => [
      'title' => t('Opened'),
      'description' => t('Total count of Opened.'),
      'bao' => 'CRM_Mailing_Event_BAO_Opened',
    ],
    'civicrm_mailing_event_unique_opened' => [
      'title' => t('Unique Opened'),
      'description' => t('Total count of Unique Opened.'),
      'bao' => 'CRM_Mailing_Event_BAO_Opened',
      'distinct' => TRUE,
    ],
    'civicrm_mailing_event_reply' => [
      'title' => t('Reply'),
      'description' => t('Total count of Replies.'),
      'bao' => 'CRM_Mailing_Event_BAO_Reply',
    ],
    'civicrm_mailing_event_subscribe' => [
      'title' => t('Subscribe'),
      'description' => t('Total count of Subscriptions.'),
      'bao' => 'CRM_Mailing_Event_BAO_Subscribe',
    ],
    'civicrm_mailing_event_trackable_url_open' => [
      'title' => t('Trackable URL Open'),
      'description' => t('Total count of Trackable URL Opened.'),
      'bao' => 'CRM_Mailing_Event_BAO_TrackableURLOpen',
    ],
    'civicrm_mailing_event_unsubscribe' => [
      'title' => t('Unsubscribe'),
      'description' => t('Total count of Unsubscribes.'),
      'bao' => 'CRM_Mailing_Event_BAO_Unsubscribe',
    ],
  ];

  foreach ($civicrm_mailing_events as $table => $value) {
    $key = str_replace('civicrm_mailing_event_', '', $table);
    $data['civicrm_mailing'][$key] = [
      'title' => $value['title'],
      'help' => $value['description'],
      'real field' => 'id',
      'field' => [
        'id' => 'civicrm_entity_mailing_event',
        'bao' => $value['bao'],
        'distinct' => !empty($value['distinct']) ? TRUE : FALSE,
      ],
    ];
  }

  $data['civicrm_mailing']['opened_rate'] = [
    'title' => t('Opened Rate'),
    'help' => t('Total count of Opened Rate.'),
    'real field' => 'id',
    'field' => ['id' => 'civicrm_entity_mailing_event_opened_rate'],
  ];

  $data['civicrm_contact']['current_employer']['real field'] = 'organization_name';
  $data['civicrm_contact']['employer_id']['field']['id'] = 'standard';
  $data['civicrm_contribution']['contribution_source']['real field'] = 'source';
  $data['civicrm_address']['state_province_id']['filter']['id'] = 'civicrm_entity_civicrm_address_state_province';
}

/**
 * Determines whether the entity type the field appears in is CiviCRM based.
 *
 * Overridden version of _views_field_get_entity_type_storage to support
 * the CiviCRM Entity storage.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage definition.
 *
 * @return \Drupal\civicrm_entity\CiviEntityStorage|bool
 *   Returns the entity type storage if supported.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 *
 * @see _views_field_get_entity_type_storage()
 */
function _civicrm_entity_field_get_entity_type_storage(FieldStorageConfigInterface $field_storage) {
  $result = FALSE;
  $entity_type_manager = \Drupal::entityTypeManager();
  if ($entity_type_manager->hasDefinition($field_storage->getTargetEntityTypeId())) {
    $storage = $entity_type_manager->getStorage($field_storage->getTargetEntityTypeId());
    $result = $storage instanceof CiviEntityStorage ? $storage : FALSE;
  }
  return $result;
}

/**
 * Overridden function to support custom storage.
 *
 * Copy and paste of views_field_default_views_data to support our entity
 * type storage check.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field definition.
 *
 * @return array
 *   The default views data for the field.
 *
 * @see views_field_default_views_data()
 */
function civicrm_entity_field_default_views_data(FieldStorageConfigInterface $field_storage) {
  $data = [];

  // Check the field type is available.
  if (!\Drupal::service('plugin.manager.field.field_type')->hasDefinition($field_storage->getType())) {
    return $data;
  }
  // Check the field storage has fields.
  if (!$field_storage->getBundles()) {
    return $data;
  }

  // Ignore custom storage too.
  if ($field_storage->hasCustomStorage()) {
    return $data;
  }

  // Check whether the entity type storage is supported.
  $storage = _civicrm_entity_field_get_entity_type_storage($field_storage);
  if (!$storage) {
    return $data;
  }

  $field_name = $field_storage->getName();
  $field_columns = $field_storage->getColumns();

  // Grab information about the entity type tables.
  // We need to join to both the base table and the data table, if available.
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $entity_type = $entity_type_manager->getDefinition($entity_type_id);
  // EDIT: We ensure the base table is set to the entity type ID, which matches
  // the CiviCRM table pattern of `civicrm_*`.
  if (!$base_table = $entity_type->getBaseTable()) {
    // We cannot do anything if for some reason there is no base table.
    return $data;
  }
  $entity_tables = [$base_table => $entity_type_id];
  // Some entities may not have a data table.
  $data_table = $entity_type->getDataTable();
  if ($data_table) {
    $entity_tables[$data_table] = $entity_type_id;
  }
  $entity_revision_table = $entity_type->getRevisionTable();
  $supports_revisions = $entity_type->hasKey('revision') && $entity_revision_table;
  if ($supports_revisions) {
    $entity_tables[$entity_revision_table] = $entity_type_id;
    $entity_revision_data_table = $entity_type->getRevisionDataTable();
    if ($entity_revision_data_table) {
      $entity_tables[$entity_revision_data_table] = $entity_type_id;
    }
  }

  // Description of the field tables.
  // @todo Generalize this code to make it work with any table layout. See
  //   https://www.drupal.org/node/2079019.
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $storage->getTableMapping();
  $field_tables = [
    EntityStorageInterface::FIELD_LOAD_CURRENT => [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'alias' => "{$entity_type_id}__{$field_name}",
    ],
  ];
  if ($supports_revisions) {
    $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION] = [
      'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
      'alias' => "{$entity_type_id}_revision__{$field_name}",
    ];
  }

  // Determine if the fields are translatable.
  $bundles_names = $field_storage->getBundles();
  $translation_join_type = FALSE;
  $fields = [];
  $translatable_configs = [];
  $untranslatable_configs = [];
  $untranslatable_config_bundles = [];

  foreach ($bundles_names as $bundle) {
    $fields[$bundle] = FieldConfig::loadByName($entity_type->id(), $bundle, $field_name);
  }
  /** @var \Drupal\field\FieldConfigInterface $config_entity */
  foreach ($fields as $bundle => $config_entity) {
    if (!empty($config_entity)) {
      if ($config_entity->isTranslatable()) {
        $translatable_configs[$bundle] = $config_entity;
      }
      else {
        $untranslatable_configs[$bundle] = $config_entity;
      }
    }
    else {
      // https://www.drupal.org/node/2451657#comment-11462881
      \Drupal::logger('views')->error(
        t('A non-existent config entity name returned by FieldStorageConfigInterface::getBundles(): field name: %field, bundle: %bundle',
          ['%field' => $field_name, '%bundle' => $bundle]
        ));
    }
  }

  // If the field is translatable on all the bundles, there will be a join on
  // the langcode.
  if (!empty($translatable_configs) && empty($untranslatable_configs)) {
    $translation_join_type = 'language';
  }
  // If the field is translatable only on certain bundles, there will be a join
  // on langcode OR bundle name.
  elseif (!empty($translatable_configs) && !empty($untranslatable_configs)) {
    /** @var \Drupal\field\FieldConfigInterface $config */
    foreach ($untranslatable_configs as $config) {
      $untranslatable_config_bundles[] = $config->getTargetBundle();
    }
    $translation_join_type = 'language_bundle';
  }

  // Build the relationships between the field table and the entity tables.
  $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT]['alias'];
  if ($data_table) {
    // Tell Views how to join to the base table, via the data table.
    $data[$table_alias]['table']['join'][$data_table] = [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];
  }
  else {
    // If there is no data table, just join directly.
    $data[$table_alias]['table']['join'][$base_table] = [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];
  }

  if ($translation_join_type === 'language_bundle') {
    $data[$table_alias]['table']['join'][$data_table]['join_id'] = 'field_or_language_join';
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'left_field' => 'langcode',
      'field' => 'langcode',
    ];
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'field' => 'bundle',
      'value' => $untranslatable_config_bundles,
    ];
  }
  elseif ($translation_join_type === 'language') {
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'left_field' => 'langcode',
      'field' => 'langcode',
    ];
  }

  if ($supports_revisions) {
    $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION]['alias'];
    if ($entity_revision_data_table) {
      // Tell Views how to join to the revision table, via the data table.
      $data[$table_alias]['table']['join'][$entity_revision_data_table] = [
        'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
        'left_field' => $entity_type->getKey('revision'),
        'field' => 'revision_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }
    else {
      // If there is no data table, just join directly.
      $data[$table_alias]['table']['join'][$entity_revision_table] = [
        'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
        'left_field' => $entity_type->getKey('revision'),
        'field' => 'revision_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }
    if ($translation_join_type === 'language_bundle') {
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['join_id'] = 'field_or_language_join';
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'left_field' => 'langcode',
        'field' => 'langcode',
      ];
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'value' => $untranslatable_config_bundles,
        'field' => 'bundle',
      ];
    }
    elseif ($translation_join_type === 'language') {
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'left_field' => 'langcode',
        'field' => 'langcode',
      ];
    }
  }

  $group_name = $entity_type->getLabel();
  // Get the list of bundles the field appears in.
  $bundles_names = $field_storage->getBundles();
  // Build the list of additional fields to add to queries.
  $add_fields = ['delta', 'langcode', 'bundle'];
  foreach (array_keys($field_columns) as $column) {
    $add_fields[] = $table_mapping->getFieldColumnName($field_storage, $column);
  }
  // Determine the label to use for the field. We don't have a label available
  // at the field level, so we just go through all fields and take the one
  // which is used the most frequently.
  [$label, $all_labels] = views_entity_field_label($entity_type_id, $field_name);

  // Expose data for the field as a whole.
  foreach ($field_tables as $type => $table_info) {
    $table = $table_info['table'];
    $table_alias = $table_info['alias'];

    if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
      $group = $group_name;
      $field_alias = $field_name;
    }
    else {
      $group = t('@group (historical data)', ['@group' => $group_name]);
      $field_alias = $field_name . '-revision_id';
    }

    $data[$table_alias][$field_alias] = [
      'group' => $group,
      'title' => $label,
      'title short' => $label,
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
    ];

    // Go through and create a list of aliases for all possible combinations of
    // entity type + name.
    $aliases = [];
    $also_known = [];
    foreach ($all_labels as $label_name => $true) {
      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        if ($label != $label_name) {
          $aliases[] = [
            'base' => $base_table,
            'group' => $group_name,
            'title' => $label_name,
            'help' => t('This is an alias of @group: @field.', [
              '@group' => $group_name,
              '@field' => $label,
            ]),
          ];
          $also_known[] = t('@group: @field', [
            '@group' => $group_name,
            '@field' => $label_name,
          ]);
        }
      }
      elseif ($supports_revisions && $label != $label_name) {
        $aliases[] = [
          'base' => $table,
          'group' => t('@group (historical data)', ['@group' => $group_name]),
          'title' => $label_name,
          'help' => t('This is an alias of @group: @field.', [
            '@group' => $group_name,
            '@field' => $label,
          ]),
        ];
        $also_known[] = t('@group (historical data): @field', [
          '@group' => $group_name,
          '@field' => $label_name,
        ]);
      }
    }
    if ($aliases) {
      $data[$table_alias][$field_alias]['aliases'] = $aliases;
      // The $also_known variable contains markup that is HTML escaped and that
      // loses safeness when imploded. The help text is used in #description
      // and therefore XSS admin filtered by default. Escaped HTML is not
      // altered by XSS filtering, therefore it is safe to just concatenate the
      // strings. Afterwards we mark the entire string as safe, so it won't be
      // escaped, no matter where it is used.
      // Considering the dual use of this help data (both as metadata and as
      // help text), other patterns such as use of #markup would not be correct
      // here.
      $data[$table_alias][$field_alias]['help'] = Markup::create($data[$table_alias][$field_alias]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
    }

    $keys = array_keys($field_columns);
    $real_field = reset($keys);
    $data[$table_alias][$field_alias]['field'] = [
      'table' => $table,
      'id' => 'field',
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      // Provide a real field for group by.
      'real field' => $field_alias . '_' . $real_field,
      'additional fields' => $add_fields,
      // Default the element type to div, let the UI change it if necessary.
      'element type' => 'div',
      'is revision' => $type == EntityStorageInterface::FIELD_LOAD_REVISION,
    ];
  }

  // Expose data for each field property individually.
  foreach ($field_columns as $column => $attributes) {
    $allow_sort = TRUE;

    // Identify filters and arguments for each column based on field type.
    switch ($attributes['type']) {
      case 'int':
      case 'mediumint':
      case 'tinyint':
      case 'bigint':
      case 'serial':
      case 'numeric':
      case 'float':
        $filter = 'numeric';
        $argument = 'numeric';
        $sort = 'standard';
        if ($field_storage->getType() == 'boolean') {
          $filter = 'boolean';
        }
        break;

      case 'text':
      case 'blob':
        // It does not make sense to sort by blob or text.
        $allow_sort = FALSE;
      default:
        $filter = 'string';
        $argument = 'string';
        $sort = 'standard';
        break;
    }

    if (count($field_columns) == 1 || $column == 'value') {
      $title = t('@label (@name)', [
        '@label' => $label,
        '@name' => $field_name,
      ]);
      $title_short = $label;
    }
    else {
      $title = t('@label (@name:@column)', [
        '@label' => $label,
        '@name' => $field_name,
        '@column' => $column,
      ]);
      $title_short = t('@label:@column', [
        '@label' => $label,
        '@column' => $column,
      ]);
    }

    // Expose data for the property.
    foreach ($field_tables as $type => $table_info) {
      $table = $table_info['table'];
      $table_alias = $table_info['alias'];

      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        $group = $group_name;
      }
      else {
        $group = t('@group (historical data)', ['@group' => $group_name]);
      }
      $column_real_name = $table_mapping->getFieldColumnName($field_storage, $column);

      // Load all the fields from the table by default.
      $additional_fields = $table_mapping->getAllColumns($table);

      $data[$table_alias][$column_real_name] = [
        'group' => $group,
        'title' => $title,
        'title short' => $title_short,
        'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
      ];

      // Create a list of aliases for all possible combinations of
      // entity type + name.
      $aliases = [];
      $also_known = [];
      foreach ($all_labels as $label_name => $true) {
        if ($label != $label_name) {
          if (count($field_columns) == 1 || $column == 'value') {
            $alias_title = t('@label (@name)', [
              '@label' => $label_name,
              '@name' => $field_name,
            ]);
          }
          else {
            $alias_title = t('@label (@name:@column)', [
              '@label' => $label_name,
              '@name' => $field_name,
              '@column' => $column,
            ]);
          }
          $aliases[] = [
            'group' => $group_name,
            'title' => $alias_title,
            'help' => t('This is an alias of @group: @field.', [
              '@group' => $group_name,
              '@field' => $title,
            ]),
          ];
          $also_known[] = t('@group: @field', [
            '@group' => $group_name,
            '@field' => $title,
          ]);
        }
      }
      if ($aliases) {
        $data[$table_alias][$column_real_name]['aliases'] = $aliases;
        // The $also_known variable contains markup that is HTML escaped and
        // that loses safeness when imploded. The help text is used in
        // #description and therefore XSS admin filtered by default. Escaped
        // HTML is not altered by XSS filtering, therefore it is safe to just
        // concatenate the strings. Afterwards we mark the entire string as
        // safe, so it won't be escaped, no matter where it is used.
        // Considering the dual use of this help data (both as metadata and as
        // help text), other patterns such as use of #markup would not be
        // correct here.
        $data[$table_alias][$column_real_name]['help'] = Markup::create($data[$table_alias][$column_real_name]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
      }

      $data[$table_alias][$column_real_name]['argument'] = [
        'field' => $column_real_name,
        'table' => $table,
        'id' => $argument,
        'additional fields' => $additional_fields,
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'empty field name' => t('- No value -'),
      ];
      $data[$table_alias][$column_real_name]['filter'] = [
        'field' => $column_real_name,
        'table' => $table,
        'id' => $filter,
        'additional fields' => $additional_fields,
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'allow empty' => TRUE,
      ];
      if (!empty($allow_sort)) {
        $data[$table_alias][$column_real_name]['sort'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $sort,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
      }

      // Set click sortable if there is a field definition.
      if (isset($data[$table_alias][$field_name]['field'])) {
        $data[$table_alias][$field_name]['field']['click sortable'] = $allow_sort;
      }

      // Expose additional delta column for multiple value fields.
      if ($field_storage->isMultiple()) {
        $title_delta = t('@label (@name:delta)', [
          '@label' => $label,
          '@name' => $field_name,
        ]);
        $title_short_delta = t('@label:delta', ['@label' => $label]);

        $data[$table_alias]['delta'] = [
          'group' => $group,
          'title' => $title_delta,
          'title short' => $title_short_delta,
          'help' => t('Delta - Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
        ];
        $data[$table_alias]['delta']['field'] = [
          'id' => 'numeric',
        ];
        $data[$table_alias]['delta']['argument'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'numeric',
          'additional fields' => $additional_fields,
          'empty field name' => t('- No value -'),
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
        $data[$table_alias]['delta']['filter'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'numeric',
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'allow empty' => TRUE,
        ];
        $data[$table_alias]['delta']['sort'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'standard',
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
      }
    }
  }

  return $data;
}

/**
 * Implements hook_views_plugins_argument_validator_alter().
 */
function civicrm_entity_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:civicrm_contact']['class'] = 'Drupal\civicrm_entity\Plugin\views\argument_validator\CivicrmContact';
}
