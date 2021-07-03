<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

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

  protected function doSetupViewWithRelationships() {
    $this->addRelationshipToDisplay('name[civicrm_activity.contact]', [
      // Set relationship to source
      'options[record_type_id]' => '2',
    ]);
    $this->addRelationshipToDisplay('name[civicrm_contact.user]');
    $this->addFieldToDisplay('name[civicrm_contact.display_name]');
  }

  protected function assertViewWithRelationshipsResults() {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
    $assert_session->pageTextContains('Johnny Appleseed');
  }

}
