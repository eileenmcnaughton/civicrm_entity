<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Core\Database\Database;
use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityTestBase;

final class CivicrmContactUserRelationshipTest extends CivicrmEntityTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'civicrm_entity_views_test',
  ];

  public function setUp(): void {
    if ($this->getName() === 'testWithSeperateDatabase' && empty(getenv('SIMPLETEST_CIVICRM_DB'))) {
      $this->markTestSkipped("Cannot run {$this->getName()} without specifying SIMPLETEST_CIVICRM_DB as a seperate database.");
    }

    parent::setUp();

    $this->enableCivicrmEntityTypes(['civicrm_activity', 'civicrm_contact']);

    $user = $this->createUser([], 'johnny');
    $civicrm_api = $this->container->get('civicrm_entity.api');
    $result = $civicrm_api->save('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Johnny',
      'last_name' => 'Appleseed',
      'email' => 'johnny@example.com',
    ]);
    $contact_id = $result['id'];

    // Link our Drupal user to the CiviCRM contact.
    \CRM_Core_BAO_UFMatch::create([
      'uf_id' => $user->id(),
      'contact_id' => $contact_id,
      'uf_name' => $user->getEmail(),
    ]);

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

    // Disable automatic live preview to make the sequence of calls clearer. And
    // prevent errors on saving the view with the preview ajax load that are
    // cancelled.
    //
    // We also want the advanced column to be open, so that it's easier to add
    // relationships.
    \Drupal::configFactory()
      ->getEditable('views.settings')
      ->set('ui.always_live_preview', FALSE)
      ->set('ui.show.advanced_column', TRUE)
      ->save();
  }

    /**
   * {@inheritdoc}
   */
  protected function changeDatabasePrefix() {
    parent::changeDatabasePrefix();
    // Change the CiviCRM connection to use the seperate database.
    if ($this->getName() === 'testWithSeperateDatabase') {
      $db_url = getenv('SIMPLETEST_CIVICRM_DB');
      Database::removeConnection('civicrm_test');
      Database::removeConnection('civicrm');

      $database = Database::convertDbUrlToConnectionInfo($db_url, isset($this->root) ? $this->root : DRUPAL_ROOT);
      Database::addConnectionInfo('civicrm_test', 'default', $database);
      Database::addConnectionInfo('civicrm', 'default', $database);
    }
  }

  public function testRelationship() {
    $this->doTest();
  }

  public function testWithSeperateDatabase() {
    $this->doTest();
  }

  private function doTest() {
    $this->drupalGet('/activity-contact-user-bug');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
  }

}
