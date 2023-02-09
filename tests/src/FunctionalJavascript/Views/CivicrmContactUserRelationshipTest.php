<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Core\Database\Database;
use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityTestBase;

/**
 * Tests for CiviCRM Contact to User Views Relationships.
 */
final class CivicrmContactUserRelationshipTest extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo fix config schema from this module for Views.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'civicrm_entity_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    if ($this->getName() === 'testWithSeperateDatabase' && empty(getenv('SIMPLETEST_CIVICRM_DB'))) {
      $this->markTestSkipped("Cannot run {$this->getName()} without specifying SIMPLETEST_CIVICRM_DB as a seperate database.");
    }
    parent::setUp();

    $admin_user = $this->createUser([
      'access content',
      'administer civicrm entity',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes(['civicrm_activity', 'civicrm_contact']);

    // Create the contact first, so that `civicrm_user_insert` matches the
    // created test user to this contact automatically.
    $civicrm_api = $this->container->get('civicrm_entity.api');
    $result = $civicrm_api->save('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Johnny',
      'last_name' => 'Appleseed',
      'email' => 'johnny@example.com',
    ]);
    $contact_id = $result['id'];

    $user = $this->createUser([], 'johnny');

    // Verify the user and contact linked.
    $fetched_contact_id = \CRM_Core_BAO_UFMatch::getContactId($user->id());
    self::assertEquals($contact_id, $fetched_contact_id);

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
    // Change the CiviCRM connection to use the separate database.
    if ($this->getName() === 'testWithSeperateDatabase') {
      $db_url = getenv('SIMPLETEST_CIVICRM_DB');
      Database::removeConnection('civicrm_test');
      Database::removeConnection('civicrm');

      $database = Database::convertDbUrlToConnectionInfo($db_url, $this->root ?? DRUPAL_ROOT);
      Database::addConnectionInfo('civicrm_test', 'default', $database);
      Database::addConnectionInfo('civicrm', 'default', $database);
    }
  }

  /**
   * Test the relationship with a single database.
   */
  public function testRelationship() {
    $this->doTest();
  }

  /**
   * Test the relationship using a seperate CiviCRM database.
   */
  public function testWithSeperateDatabase() {
    self::assertNotEquals(
      Database::getConnectionInfo('civicrm_test'),
      Database::getConnectionInfo()
    );
    $this->doTest();
  }

  /**
   * Performs the test.
   */
  private function doTest() {
    $this->drupalGet('/activity-contact-user-bug');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Meeting about new seeds');
  }

}
