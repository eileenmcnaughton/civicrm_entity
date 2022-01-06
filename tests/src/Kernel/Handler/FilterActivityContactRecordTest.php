<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\views\Views;

/**
 * Test ActivityContactRecord.
 *
 * @group civicrim_entity
 */
final class FilterActivityContactRecordTest extends KernelHandlerTestBase {

  public static $testViews = ['test_view'];

  protected $columnMap = [
    'contact_type' => 'contact_type',
    'display_name' => 'display_name',
  ];

  /**
   * Tests general offset.
   */
  public function testOffset() {
  }

  /**
   * Tests the filter operator between/not between.
   */
  protected function testBetween() {
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
