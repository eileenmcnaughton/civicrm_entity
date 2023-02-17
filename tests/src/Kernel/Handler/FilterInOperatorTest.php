<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\views\Views;

/**
 * Test InOperator.
 *
 * @group civicrim_entity
 */
final class FilterInOperatorTest extends KernelHandlerTestBase {

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
   * Test civicrm_entity_in_operator plugin.
   */
  public function testFilterInOperatorSimple() {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'contact_type' => [
        'id' => 'contact_type',
        'table' => 'civicrm_contact',
        'field' => 'contact_type',
        'value' => ['Individual' => 'Individual'],
        'operator' => 'or',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'contact_type',
        'plugin_id' => 'list_field',
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
      [
        'display_name' => 'Jane Doe',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(4, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    // @codingStandardsIgnoreStart
    // $view->destroy();
    // $view->setDisplay();
    // $view->displayHandlers->get('default')->overrideOption('filters', [
    //   'contact_type' => [
    //     'id' => 'contact_type',
    //     'table' => 'civicrm_contact',
    //     'field' => 'contact_type',
    //     'value' => ['Individual' => 'Individual'],
    //     'operator' => 'not',
    //     'entity_type' => 'civicrm_contact',
    //     'entity_field' => 'contact_type',
    //     'plugin_id' => 'list_field',
    //   ],
    // ]);
    // $view->preExecute();
    // $view->execute();
    // $expected_result = [
    //   [
    //     'organization_name' => 'Default organization',
    //     'contact_type' => 'Organization',
    //   ],
    //   [
    //     'organization_name' => 'The Trevor Project',
    //     'contact_type' => 'Organization',
    //   ],
    // ];
    // $this->assertCount(2, $view->result);
    // $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    // @codingStandardsIgnoreEnd

    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_select_1' => [
        'id' => 'test_select_1',
        'field' => 'test_select_1',
        'table' => 'civicrm_value_test_1',
        'value' => [1 => '1', 2 => '2'],
        'operator' => 'in',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_in_operator',
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

    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_select_1' => [
        'id' => 'test_select_1',
        'field' => 'test_select_1',
        'table' => 'civicrm_value_test_1',
        'value' => [1 => '1', 2 => '2'],
        'operator' => 'not in',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_in_operator',
      ],
    ]);

    $this->executeView($view);

    $expected_result = [
      [
        'display_name' => 'John Doe',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'Jane Doe',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    /** @var \Drupal\civicrm_entity\CiviCrmApi $civicrm_api */
    $civicrm_api = $this->container->get('civicrm_entity.api');

    $option_group_result = $civicrm_api->save('OptionGroup', ['name' => 'Test options']);
    $option_group_result = reset($option_group_result['values']);

    $options = [
      ['label' => 'Test', 'value' => 1],
      ['label' => 'Test 1', 'value' => 2],
      ['label' => 'Test 2', 'value' => 3],
    ];

    foreach ($options as $option) {
      $civicrm_api->save('OptionValue', $option + ['option_group_id' => $option_group_result['id']]);
    }

    $result = $civicrm_api->save('CustomGroup', [
      'title' => 'Test',
      'extends' => 'Individual',
    ]);

    $result = reset($result['values']);

    $civicrm_api->save('CustomField', [
      'custom_group_id' => $result['id'],
      'label' => 'Test select',
      'serialize' => 1,
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
      'option_group_id' => $option_group_result['id'],
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
        'custom_1' => [1],
      ],
      [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'contact_type' => 'Individual',
        'custom_1' => [2],
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'contact_type' => 'Individual',
        'custom_1' => [],
      ],
      [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'contact_type' => 'Individual',
        // @todo There is bug with civicrm_entity_in_operator for custom fields
        // not yet initialized. These are not included even if it has no value
        // for "not in" operator. Remove this line once the bug is fixed.
        'custom_1' => [],
      ],
      [
        'organization_name' => 'The Trevor Project',
        'contact_type' => 'organization',
      ],
    ];
  }

}
