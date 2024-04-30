<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

/**
 * Tests for CiviCRM Address Views.
 */
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

    $result = $civicrm_api->save('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john@example.com',
    ]);
    $contact_id = $result['id'];
    $civicrm_api->save('Address', [
      'contact_id' => $contact_id,
      'location_type_id' => 'Billing',
      'street_address' => '3820 Vitruvian Way',
      'country_id' => 'US',
      'state_province_id' => 'Texas',
      'postal_code' => 75001,
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
    $assert_session->pageTextContains('United States');
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
    $assert_session->pageTextContains('United States');
    $assert_session->pageTextContainsOnce('35005');
    $assert_session->pageTextContainsOnce('Alabama');
    $assert_session->pageTextContainsOnce('Test');
    $assert_session->pageTextContainsOnce('Johnny Appleseed');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithFilters() {
    $this->addFilterToDisplay('name[civicrm_address.location_type_id]', [
      'options[operator]' => 'or',
      'options[value][]' => [1],
    ]);

    $this->addFilterToDisplay('name[civicrm_address.state_province_id]', [
      'options[operator]' => 'or',
      'options[value][]' => [1000],
    ]);

    $this->addFieldToDisplay('name[civicrm_address.location_type_id]');
    $this->addFieldToDisplay('name[civicrm_address.country_id]');
    $this->addFieldToDisplay('name[civicrm_address.postal_code]');
    $this->addFieldToDisplay('name[civicrm_address.state_province_id]');
    $this->addFieldToDisplay('name[civicrm_address.street_address]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithFiltersResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Home');
    $assert_session->pageTextContains('United States');
    $assert_session->pageTextContainsOnce('35005');
    $assert_session->pageTextContainsOnce('Alabama');
    $assert_session->pageTextContainsOnce('Test');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithSorts() {
    $this->addSortToDisplay('name[civicrm_address.contact_id]', [
      'options[order]' => 'DESC',
    ]);

    $this->addFieldToDisplay('name[civicrm_address.location_type_id]');
    $this->addFieldToDisplay('name[civicrm_address.country_id]');
    $this->addFieldToDisplay('name[civicrm_address.postal_code]');
    $this->addFieldToDisplay('name[civicrm_address.state_province_id]');
    $this->addFieldToDisplay('name[civicrm_address.street_address]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithSortsResults() {
    $assert_session = $this->assertSession();
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Texas');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'United States');
    $assert_session->elementTextContains('css', '.views-row:first-child', '75001');
    $assert_session->elementTextContains('css', '.views-row:first-child', '3820 Vitruvian Way');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithArguments() {
    $this->addArgumentToDisplay('name[civicrm_address.id]');
    $this->addFieldToDisplay('name[civicrm_address.location_type_id]');
    $this->addFieldToDisplay('name[civicrm_address.country_id]');
    $this->addFieldToDisplay('name[civicrm_address.postal_code]');
    $this->addFieldToDisplay('name[civicrm_address.state_province_id]');
    $this->addFieldToDisplay('name[civicrm_address.street_address]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithArgumentsResults(array $arguments) {
    $assert_session = $this->assertSession();

    switch ($arguments[0]) {
      case 1:
        $assert_session->pageTextContainsOnce('Home');
        $assert_session->pageTextContains('United States');
        $assert_session->pageTextContainsOnce('35005');
        $assert_session->pageTextContainsOnce('Alabama');
        $assert_session->pageTextContainsOnce('Test');
        break;

      case 2:
        $assert_session->pageTextContainsOnce('Billing');
        $assert_session->pageTextContains('United States');
        $assert_session->pageTextContainsOnce('75001');
        $assert_session->pageTextContainsOnce('Texas');
        $assert_session->pageTextContainsOnce('3820 Vitruvian Way');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dataArgumentValues() {
    yield [[1]];
    yield [[2]];
  }

}
