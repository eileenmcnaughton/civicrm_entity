<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\NestedArray;

/**
 * Hook implementations for themes.
 */
class ViewsHooks {

  /**
   * Constructor for EntityHooks.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_views_data_alter().
   *
   * @note Copy and paste of views_views_data_alter to support our storage check.
   *
   * @see views_views_data()
   * @see views_views_data_alter()
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data) {
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

    if (isset($data['civicrm_address'])) {
      $data['civicrm_address']['distance'] = [
        'title' => t('Distance'),
        'help' => t('Calculated distance from proximity filter location.'),
        'field' => [
          'id' => 'civicrm_entity_distance',
        ],
        'sort' => [
          'id' => 'civicrm_entity_distance_sort',
        ],
      ];
    }
  }

}
