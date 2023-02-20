<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\views\Views;

/**
 * Test Date.
 *
 * @group civicrim_entity
 */
final class FilterDateTest extends KernelHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $columnMap = [
    'contact_type' => 'contact_type',
    'display_name' => 'display_name',
  ];

  /**
   * Tests general offset.
   */
  public function testOffset() {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView('test_view');

    // Test offset for simple operator.
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_date_1' => [
        'id' => 'test_date_1',
        'table' => 'civicrm_value_test_1',
        'field' => 'test_date_1',
        'value' => ['type' => 'offset', 'value' => '+3 days'],
        'operator' => '>',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_date',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'Jane Smith',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(1, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();

    // Test offset for between operator.
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_date_1' => [
        'id' => 'test_date_1',
        'table' => 'civicrm_value_test_1',
        'field' => 'test_date_1',
        'value' => ['type' => 'offset', 'max' => '+7 days', 'min' => '-7 days'],
        'operator' => 'between',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_date',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'John Smith',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'Jane Smith',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * Tests the filter operator between/not between.
   */
  public function testBetween() {
    $view = Views::getView('test_view');

    // Test between with just max.
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_date_1' => [
        'id' => 'test_date_1',
        'table' => 'civicrm_value_test_1',
        'field' => 'test_date_1',
        'value' => ['type' => 'offset', 'max' => '+7 days'],
        'operator' => 'between',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_date',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'Jane Smith',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(1, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();

    // Test not between with min and max.
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_date_1' => [
        'id' => 'test_date_1',
        'table' => 'civicrm_value_test_1',
        'field' => 'test_date_1',
        'value' => ['type' => 'offset', 'max' => '+3 days', 'min' => '-7 days'],
        'operator' => 'not between',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_date',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'Jane Smith',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'John Doe',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();

    // Test not between with just max.
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_date_1' => [
        'id' => 'test_date_1',
        'table' => 'civicrm_value_test_1',
        'field' => 'test_date_1',
        'value' => ['type' => 'offset', 'max' => '+3 days'],
        'operator' => 'not between',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_date',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'John Smith',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'Jane Smith',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'John Doe',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(3, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    /** @var \Drupal\civicrm_entity\CiviCrmApi $civicrm_api */
    $civicrm_api = $this->container->get('civicrm_entity.api');

    $result = $civicrm_api->save('CustomGroup', [
      'title' => 'Test',
      'extends' => 'Individual',
    ]);

    $result = reset($result['values']);

    $civicrm_api->save('CustomField', [
      'custom_group_id' => $result['id'],
      'label' => 'Test date',
      "data_type" => 'Date',
      "html_type" => 'Select Date',
    ]);

    $contacts = $this->createSampleData();

    foreach ($contacts as $contact) {
      $civicrm_api->save('Contact', $contact);
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
