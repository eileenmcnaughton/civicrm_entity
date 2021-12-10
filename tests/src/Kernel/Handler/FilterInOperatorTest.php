<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\civicrm_entity\Kernel\CivicrmEntityTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Test InOperator.
 *
 * @group civicrim_entity
 */
final class FilterInOperatorTest extends CivicrmEntityTestBase {

  use ViewResultAssertionTrait;

  public function alter(ContainerBuilder $container) {
    // Disable mocks.
  }

  protected static $modules = [
    'views',
    'civicrm_entity_views_test',
  ];

  public static $testViews = ['test_view'];

  protected $columnMap = [
    'contact_type' => 'contact_type',
    'display_name' => 'display_name',
    // 'organization_name' => 'organization_name',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createTestViews(static::$testViews);
    $this->container->get('civicrm')->initialize();

    $params = ['name' => 'Test options'];
    $option_group_result = \CRM_Core_BAO_OptionGroup::add($params);

    $options = [
      ['label' => 'Test', 'value' => 1],
      ['label' => 'Test 1', 'value' => 2],
      ['label' => 'Test 2', 'value' => 3],
    ];

    foreach ($options as $option) {
      $params = $option + ['option_group_id' => $option_group_result->id];
      \CRM_Core_BAO_OptionValue::create($params);
    }

    $params = [
      'title' => 'Test',
      'extends' => 'Individual',
    ];

    $result = \CRM_Core_BAO_CustomGroup::create($params);

    $params = [
      'custom_group_id' => $result->id,
      'label' => 'Test select',
      'serialize' => 1,
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
      'option_group_id' => $option_group_result->id,
    ];

    $result = \CRM_Core_BAO_CustomField::create($params);

    $this->createSampleData();
  }

  /**
   * Test civicrm_entity_in_operator pllgin.
   */
  public function testFilterInOperatorSimple() {
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

    $view->preExecute();
    $view->execute();

    $expected_result = [
      [
        'display_name' => 'Emma Neal',
        'contact_type' => 'Individual',
      ],
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

    $this->assertCount(4, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    $view->destroy();
    $view->setDisplay();

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

    // $view->destroy();
    // $view->setDisplay();

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

    $view->preExecute();
    $view->execute();

    $expected_result = [
      [
        'display_name' => 'Emma Neal',
        'contact_type' => 'Individual',
      ],
      [
        'display_name' => 'John Smith',
        'contact_type' => 'Individual',
      ],
    ];

    $this->assertCount(2, $view->result);
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);

    return;

    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_select_1' => [
        'id' => 'test_select_1',
        'field' => 'test_select_1',
        'table' => 'civicrm_value_test_1',
        'value' => [1 => 1, 2 => 2],
        'operator' => 'not in',
        'entity_type' => 'civicrm_contact',
        'entity_field' => 'custom_1',
        'plugin_id' => 'civicrm_entity_in_operator',
      ],
    ]);

    $view->preExecute();
    $view->execute();

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
  }

  /**
   * Create the views configurations.
   */
  protected function createTestViews(array $views) {
    $storage = \Drupal::entityTypeManager()->getStorage('view');
    $module_handler = \Drupal::moduleHandler();

    $config_dir = \Drupal::service('extension.list.module')->getPath('civicrm_entity_views_test') . '/config/install';
    if (is_dir($config_dir) && $module_handler->moduleExists('civicrm_entity_views_test')) {
      $file_storage = new FileStorage($config_dir);
      $available_views = $file_storage->listAll('views.view.');
      foreach ($views as $id) {
        $config_name = 'views.view.' . $id;
        if (in_array($config_name, $available_views)) {
          $storage
            ->create($file_storage->read($config_name))
            ->save();
        }
      }
    }
  }

  /**
   * Create sample data.
   */
  protected function createSampleData() {
    $contacts = $this->sampleContactData();
    $contacts = array_map(function ($contact) {
      $params = [
        'first_name' => $contact['first_name'],
        'last_name' => $contact['last_name'],
        'contact_type' => $contact['contact_type'],
        'organization_name' => $contact['organization_name'],
      ];

      if (isset($contact['custom_1'])) {
        $params['custom_1'] = $contact['custom_1'];
      }

      return $params;
    }, $contacts);

    foreach ($contacts as $contact) {
      civicrm_api3('Contact', 'create', $contact);
    }
  }

}
