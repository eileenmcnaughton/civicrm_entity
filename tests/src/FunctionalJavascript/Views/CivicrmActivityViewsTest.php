<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

/**
 * Tests for CiviCRM Activity Views.
 */
final class CivicrmActivityViewsTest extends CivicrmEntityViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityTypeId = 'civicrm_activity';

  /**
   * {@inheritdoc}
   */
  public function testAddWizardValues() {
    parent::testAddWizardValues();
    // Specific bundles are present.
    $this->assertSession()->optionExists('show[type]', 'meeting');
    $this->assertSession()->optionExists('show[type]', 'email');
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
    $civicrm_api->save('Activity', [
      'source_contact_id' => $contact_id,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting about new seeds',
      'activity_date_time' => '2011-06-02 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'We need to find more seeds!',
    ]);
    $civicrm_api->save('Activity', [
      'source_contact_id' => $contact_id,
      'activity_type_id' => 'Email',
      'subject' => 'Email about new seeds',
      'activity_date_time' => '2011-06-03 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Texas',
      'details' => 'We just got new seeds.',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupCreateView() {
    $this->addFieldToDisplay('name[civicrm_activity.details__value]');
    $this->addFieldToDisplay('name[civicrm_activity.location]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCreateViewResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
    $assert_session->pageTextContains('We need to find more seeds!');
    $assert_session->pageTextContains('Pennsylvania');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithRelationships() {
    $this->addRelationshipToDisplay('name[civicrm_activity.contact]', [
      // Set relationship to source.
      'options[record_type_id][]' => ['2'],
    ]);
    $this->addRelationshipToDisplay('name[civicrm_contact.user]');
    $this->addFieldToDisplay('name[civicrm_contact.display_name]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithRelationshipsResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
    $assert_session->pageTextContains('Johnny Appleseed');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithFilters() {
    $this->addFilterToDisplay('name[civicrm_activity.activity_type_id]', [
      'options[operator]' => 'or',
      'options[value][]' => [1],
    ]);

    $this->addFieldToDisplay('name[civicrm_activity.details__value]');
    $this->addFieldToDisplay('name[civicrm_activity.location]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithFiltersResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
    $assert_session->pageTextContains('We need to find more seeds!');
    $assert_session->pageTextContains('Pennsylvania');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithSorts() {
    $this->addSortToDisplay('name[civicrm_activity.id]', [
      'options[order]' => 'DESC',
    ]);

    $this->addFieldToDisplay('name[civicrm_activity.details__value]');
    $this->addFieldToDisplay('name[civicrm_activity.location]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithSortsResults() {
    $assert_session = $this->assertSession();
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Email about new seeds');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'We just got new seeds.');
    $assert_session->elementTextContains('css', '.views-row:first-child', 'Texas');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupViewWithArguments() {
    $this->addArgumentToDisplay('name[civicrm_activity.id]');
    $this->addFieldToDisplay('name[civicrm_activity.details__value]');
    $this->addFieldToDisplay('name[civicrm_activity.location]');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertViewWithArgumentsResults(array $arguments) {
    $assert_session = $this->assertSession();

    switch ($arguments[0]) {
      case 1:
        $assert_session->pageTextContainsOnce('Meeting about new seeds');
        $assert_session->pageTextContainsOnce('We need to find more seeds!');
        $assert_session->pageTextContainsOnce('Pennsylvania');
        break;

      case 2:
        $assert_session->pageTextContainsOnce('Email about new seeds');
        $assert_session->pageTextContainsOnce('We just got new seeds.');
        $assert_session->pageTextContainsOnce('Texas');
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
