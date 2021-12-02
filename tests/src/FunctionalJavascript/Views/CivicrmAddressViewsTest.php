<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

final class CivicrmAddressViewsTest extends CivicrmEntityViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityTypeId = 'civicrm_address';

  /**
   * {@inheritdoc}
   */
  protected function createSampleData() {
    $civicrm_api = $this->container->get('civicrm_entity.api');
    $result = $civicrm_api->save('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Johnny',
      'last_name' => 'Appleseed',
      'email' => 'johnny@example.com',
    ]);
    $contact_id = $result['id'];
    $civicrm_api->save('Address', [
      'contact_id' => $contact_id,
      'location_type_id' => 'Home',
      'street_address' => 'Test',
      'country_id' => 'US',
      'state_province_id' => 'Alabama',
      'postal_code' => 35005,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupCreateView() {
    $this->addFieldToDisplay('name[civicrm_address.location_type_id]');
    $this->addFieldToDisplay('name[civicrm_address.country_id]');
    $this->addFieldToDisplay('name[civicrm_address.postal_code]');
    $this->addFieldToDisplay('name[civicrm_address.state_province_id]');
    $this->addFieldToDisplay('name[civicrm_address.street_address]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCreateViewResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Home');
    $assert_session->pageTextContainsOnce('United States');
    $assert_session->pageTextContainsOnce('35005');
    $assert_session->pageTextContainsOnce('Alabama');
    $assert_session->pageTextContainsOnce('Test');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithRelationships() {
    $this->addRelationshipToDisplay('name[civicrm_address.contact_id]');
    $this->addRelationshipToDisplay('name[civicrm_contact.user]');
    $this->addFieldToDisplay('name[civicrm_contact.display_name]');
    $this->addFieldToDisplay('name[civicrm_address.location_type_id]');
    $this->addFieldToDisplay('name[civicrm_address.country_id]');
    $this->addFieldToDisplay('name[civicrm_address.postal_code]');
    $this->addFieldToDisplay('name[civicrm_address.state_province_id]');
    $this->addFieldToDisplay('name[civicrm_address.street_address]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithRelationshipsResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Home');
    $assert_session->pageTextContainsOnce('United States');
    $assert_session->pageTextContainsOnce('35005');
    $assert_session->pageTextContainsOnce('Alabama');
    $assert_session->pageTextContainsOnce('Test');
    $assert_session->pageTextContainsOnce('Johnny Appleseed');
  }

}
