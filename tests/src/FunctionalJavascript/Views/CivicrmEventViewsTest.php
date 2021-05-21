<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

final class CivicrmEventViewsTest extends CivicrmEntityViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityTypeId = 'civicrm_event';

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityPermissions = [
    'view event info'
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

}
