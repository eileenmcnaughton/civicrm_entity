<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

/**
 * Tests for CiviCRM Views data.
 *
 * @group civicrim_entity
 */
class CivicrmViewsDataTest extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
  ];

  /**
   * Test CiviCRM Address Views data.
   */
  public function testCivicrmAddressViewsData() {
    $views_data = $this->container->get('views.views_data');
    $civicrm_address = $views_data->get('civicrm_address');
    // Verify addresses have a relationship to contacts.
    $contact_relationship = $civicrm_address['contact_id']['relationship'];
    $this->assertEquals('civicrm_contact', $contact_relationship['base']);
    $this->assertEquals('id', $contact_relationship['base field']);

    $civicrm_contact = $views_data->get('civicrm_contact');
    $this->assertArrayHasKey('reverse__civicrm_address__contact_id', $civicrm_contact);
    $reverse_relationship = $civicrm_contact['reverse__civicrm_address__contact_id']['relationship'];
    $this->assertEquals('civicrm_address', $reverse_relationship['base']);
    $this->assertEquals('id', $reverse_relationship['base field']);
    $this->assertEquals('civicrm_contact', $reverse_relationship['field table']);
    $this->assertEquals('contact_id', $reverse_relationship['field field']);
  }

}
