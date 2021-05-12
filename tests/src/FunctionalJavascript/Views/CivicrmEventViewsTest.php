<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

final class CivicrmEventViewsTest extends CivicrmEntityViewsTestBase {

  protected static $civicrmEntityTypeId = 'civicrm_event';

  public function testAddWizardValues() {
    parent::testAddWizardValues();
    // Specific bundles are present.
    $this->assertSession()->optionExists('show[type]', 'conference');
    $this->assertSession()->optionExists('show[type]', 'workshop');
  }

  protected function createSampleData() {
    $civicrm_api = $this->container->get('civicrm_entity.api');
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
    ]);
  }

  // @todo should we have a func which returns field names and a "configureField" method to adjust it further?
  protected function addAndConfigureFields() {
    $page = $this->getSession()->getPage();
    $page->checkField('name[civicrm_event.description__value]');
    $page->checkField('name[civicrm_event.end_date]');
    $page->checkField('name[civicrm_event.start_date]');
    // Add the fields.
    $this->submitViewsDialog();

    // configure and submit the description field.
    $this->submitViewsDialog();
    // configure and submit the end date field.
    $this->submitViewsDialog();
    // configure and submit the start date field.
    $this->submitViewsDialog();
  }

  protected function assertCreateViewResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContainsOnce('Annual CiviCRM meet');
    $assert_session->pageTextContainsOnce('This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues');
    // @todo assert Start Date, End Date.
  }

}
