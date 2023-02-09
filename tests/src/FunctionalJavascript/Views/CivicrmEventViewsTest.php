<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

/**
 * Tests for CiviCRM Event Views.
 */
final class CivicrmEventViewsTest extends CivicrmEntityViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityTypeId = 'civicrm_event';

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityPermissions = [
    'view event info',
  ];

  /**
   * {@inheritdoc}
   */
  public function testAddWizardValues() {
    parent::testAddWizardValues();
    // Specific bundles are present.
    $this->assertSession()->optionExists('show[type]', 'conference');
    $this->assertSession()->optionExists('show[type]', 'workshop');
  }

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
    $civicrm_api->save('Event', [
      'title' => 'Annual CiviCRM meet',
      'summary' => 'If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now',
      'description' => 'This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20081021,
      'end_date' => 20081023,
      'is_online_registration' => 1,
      'registration_start_date' => 20080601,
      'registration_end_date' => '2008-10-15',
      'max_participants' => 100,
      'event_full_text' => 'Sorry! We are already full',
      'is_monetary' => 0,
      'is_active' => 1,
      'is_show_location' => 0,
      'created_id' => $contact_id,
    ]);
    $civicrm_api->save('Event', [
      'title' => 'Annual Drupal meet',
      'summary' => 'If you have any Drupal related issues or want to track where Drupal is heading, Sign up now',
      'description' => 'This event is intended to give brief idea about progress of Drupal and giving solutions to common user issues',
      'event_type_id' => 2,
      'is_public' => 1,
      'start_date' => 20091021,
      'end_date' => 20091023,
      'is_online_registration' => 1,
      'registration_start_date' => 20090601,
      'registration_end_date' => '2009-10-15',
      'max_participants' => 100,
      'event_full_text' => 'Sorry! We are already full',
      'is_monetary' => 0,
      'is_active' => 1,
      'is_show_location' => 0,
      'created_id' => $contact_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupCreateView() {
    $this->addFieldToDisplay('name[civicrm_event.description__value]');
    $this->addFieldToDisplay('name[civicrm_event.summary]');
    $this->addFieldToDisplay('name[civicrm_event.end_date]');
    $this->addFieldToDisplay('name[civicrm_event.start_date]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCreateViewResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Annual CiviCRM meet');
    $assert_session->pageTextContainsOnce('This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues');
    $assert_session->pageTextContainsOnce('If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now');
    $assert_session->pageTextContainsOnce('Thu, 10/23/2008 - 00:00');
    $assert_session->pageTextContainsOnce('Tue, 10/21/2008 - 00:00');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithRelationships() {
    $this->addRelationshipToDisplay('name[civicrm_event.created_id]');
    $this->addRelationshipToDisplay('name[civicrm_contact.user]');
    $this->addFieldToDisplay('name[civicrm_contact.display_name]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithRelationshipsResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Annual CiviCRM meet');
    $assert_session->pageTextContains('Johnny Appleseed');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithFilters() {
    $this->addFilterToDisplay('name[civicrm_event.event_type_id]', [
      'options[operator]' => 'or',
      'options[value][]' => [1],
    ]);

    $this->addFieldToDisplay('name[civicrm_event.description__value]');
    $this->addFieldToDisplay('name[civicrm_event.summary]');
    $this->addFieldToDisplay('name[civicrm_event.end_date]');
    $this->addFieldToDisplay('name[civicrm_event.start_date]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithFiltersResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Annual CiviCRM meet');
    $assert_session->pageTextContainsOnce('This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues');
    $assert_session->pageTextContainsOnce('If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now');
    $assert_session->pageTextContainsOnce('Thu, 10/23/2008 - 00:00');
    $assert_session->pageTextContainsOnce('Tue, 10/21/2008 - 00:00');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithSorts() {
    $this->addSortToDisplay('name[civicrm_event.id]', [
      'options[order]' => 'DESC',
    ]);

    $this->addFieldToDisplay('name[civicrm_event.description__value]');
    $this->addFieldToDisplay('name[civicrm_event.summary]');
    $this->addFieldToDisplay('name[civicrm_event.end_date]');
    $this->addFieldToDisplay('name[civicrm_event.start_date]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithSortsResults() {
    $assert_session = $this->assertSession();
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Annual Drupal meet');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'This event is intended to give brief idea about progress of Drupal and giving solutions to common user issues');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'If you have any Drupal related issues or want to track where Drupal is heading, Sign up now');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Fri, 10/23/2009 - 00:00');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Wed, 10/21/2009 - 00:00');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithArguments() {
    $this->addArgumentToDisplay('name[civicrm_event.id]');
    $this->addFieldToDisplay('name[civicrm_event.description__value]');
    $this->addFieldToDisplay('name[civicrm_event.summary]');
    $this->addFieldToDisplay('name[civicrm_event.end_date]');
    $this->addFieldToDisplay('name[civicrm_event.start_date]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithArgumentsResults(array $arguments) {
    $assert_session = $this->assertSession();

    switch ($arguments[0]) {
      case 1:
        $assert_session->pageTextContainsOnce('Annual CiviCRM meet');
        $assert_session->pageTextContainsOnce('This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues');
        $assert_session->pageTextContainsOnce('If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now');
        $assert_session->pageTextContainsOnce('Thu, 10/23/2008 - 00:00');
        $assert_session->pageTextContainsOnce('Tue, 10/21/2008 - 00:00');
        break;

      case 2:
        $assert_session->pageTextContainsOnce('Annual Drupal meet');
        $assert_session->pageTextContainsOnce('This event is intended to give brief idea about progress of Drupal and giving solutions to common user issues');
        $assert_session->pageTextContainsOnce('If you have any Drupal related issues or want to track where Drupal is heading, Sign up now');
        $assert_session->pageTextContainsOnce('Fri, 10/23/2009 - 00:00');
        $assert_session->pageTextContainsOnce('Wed, 10/21/2009 - 00:00');
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
