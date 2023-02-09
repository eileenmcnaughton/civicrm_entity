<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\views\Views;

/**
 * Test ActivityContactRecord.
 *
 * @group civicrm_entity
 */
final class FilterActivityContactRecordTest extends KernelHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_activity'];

  /**
   * {@inheritdoc}
   */
  protected $columnMap = [
    'subject' => 'subject',
    'location' => 'location',
    'details__value' => 'details__value',
  ];

  /**
   * Tests simple operator.
   */
  public function testFilter() {
    $view = Views::getView('test_activity');

    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'assignee_id' => [
        'id' => 'assignee_id',
        'table' => 'civicrm_activity',
        'field' => 'assignee_id',
        'value' => ['value' => 4],
        'operator' => '=',
        'entity_type' => 'civicrm_activity',
        'entity_field' => 'assignee_id',
        'plugin_id' => 'civicrm_entity_civicrm_activity_contact_record',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'subject' => 'Email about new seeds',
        'location' => 'Texas',
        'details__value' => 'We just got new seeds.',
      ],
    ];

    $this->assertCount(1, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();

    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'source_contact_id' => [
        'id' => 'source_contact_id',
        'table' => 'civicrm_activity',
        'field' => 'source_contact_id',
        'value' => ['value' => 3],
        'operator' => '=',
        'entity_type' => 'civicrm_activity',
        'entity_field' => 'source_contact_id',
        'plugin_id' => 'civicrm_entity_civicrm_activity_contact_record',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'subject' => 'Meeting about new seeds',
        'location' => 'Pennsylvania',
        'details__value' => 'We need to find more seeds!',
      ],
      [
        'subject' => 'Email about new seeds',
        'location' => 'Texas',
        'details__value' => 'We just got new seeds.',
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();

    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'target_id' => [
        'id' => 'target_id',
        'table' => 'civicrm_activity',
        'field' => 'target_id',
        'value' => ['value' => 4],
        'operator' => '=',
        'entity_type' => 'civicrm_activity',
        'entity_field' => 'target_id',
        'plugin_id' => 'civicrm_entity_civicrm_activity_contact_record',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'subject' => 'Meeting about new seeds',
        'location' => 'Pennsylvania',
        'details__value' => 'We need to find more seeds!',
      ],
    ];

    $this->assertCount(1, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    /** @var \Drupal\civicrm_entity\CiviCrmApi $civicrm_api */
    $civicrm_api = $this->container->get('civicrm_entity.api');

    foreach ($this->createSampleData() as $contact) {
      $civicrm_api->save('Contact', $contact);
    }

    $activities = [
      [
        'subject' => 'Meeting about new seeds',
        'activity_type_id' => 'Meeting',
        'location' => 'Pennsylvania',
        'details' => 'We need to find more seeds!',
        // Jane Smith.
        'source_contact_id' => 3,
        // Jane Smith.
        'assignee_id' => 3,
        // John Doe.
        'target_id' => 4,
      ],
      [
        'subject' => 'Email about new seeds',
        'activity_type_id' => 'Email',
        'location' => 'Texas',
        'details' => 'We just got new seeds.',
        // Jane Smith.
        'source_contact_id' => 3,
        // John Doe.
        'assignee_id' => 4,
        // John Smith.
        'target_id' => 2,
      ],
    ];

    foreach ($activities as $activity) {
      $civicrm_api->save('Activity', $activity);
    }

    drupal_flush_all_caches();
  }

  /**
   * Create sample data.
   */
  protected function createSampleData() {
    return [
      [
        'first_name' => 'John',
        'last_name' => 'Smith',
        'contact_type' => 'Individual',
        'custom_1' => date('Y-m-d H:i:s', strtotime('-5 days')),
      ],
      [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'contact_type' => 'Individual',
        'custom_1' => date('Y-m-d H:i:s', strtotime('+5 days')),
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'contact_type' => 'Individual',
        'custom_1' => date('Y-m-d H:i:s', strtotime('-1 month')),
      ],
    ];
  }

}
