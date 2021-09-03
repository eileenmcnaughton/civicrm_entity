<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityTestBase;

/**
 * @requires module fullcalendar_view
 */
final class ActivityFullcalendarViewTest extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo fix config schema from this module for Views.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The test activity.
   *
   * @var mixed
   */
  private $activityId;


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'civicrm_entity_fullcalendar_test',
  ];



  /**
   * {@inheritdoc}
   *
   * @todo needs more data.
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->createUser([
      'access content',
      'administer civicrm entity',
    ]);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes(['civicrm_activity', 'civicrm_contact']);

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

    $date = new DrupalDateTime('now');
    $result = $civicrm_api->save('Activity', [
      'source_contact_id' => $contact_id,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting about new seeds',
      'activity_date_time' => $date->format('Y-m-d') . ' 12:00:00',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'We need to find more seeds!',
    ]);
    $this->activityId = $result['id'];

    \Drupal::configFactory()
      ->getEditable('views.settings')
      ->set('ui.always_live_preview', FALSE)
      ->set('ui.show.advanced_column', TRUE)
      ->save();
  }

  public function testFullcalendarDisplay() {
    $this->drupalGet('/activity-fullcalendar');
    $this->createScreenshot('../calendar.png');
    $fullcalendar = $this->assertSession()->elementExists('css', '.js-drupal-fullcalendar');
    $this->assertSession()->elementExists('css', '.fc-event-container', $fullcalendar);
    $this->assertSession()->elementTextContains('css', '.fc-event-container .fc-time', '12:00 pm');
    $this->assertSession()->elementTextContains('css', '.fc-event-container .fc-title', 'Meeting about new seeds');
  }

  /**
   * @group debug
   */
  public function testActivityDragAndUpdate() {
    $previous_day = new DrupalDateTime('-1 day');
    $previous_day_formatted = $previous_day->format('Y-m-d');

    $this->drupalGet('/activity-fullcalendar');
    $fullcalendar = $this->assertSession()->elementExists('css', '.js-drupal-fullcalendar');
    $event = $this->assertSession()->elementExists('css', '.fc-event-container .fc-event', $fullcalendar);
    $destination = $this->assertSession()->elementExists('css', ".fc-bg [data-date='$previous_day_formatted']", $fullcalendar);

    // @todo WebDriver\Exception\UnexpectedAlertOpen exception fix
    // Why doesn't \Drupal\FunctionalJavascriptTests\Ajax\CommandsTest::testAjaxCommands fail?
    // Or \Drupal\FunctionalJavascriptTests\BrowserWithJavascriptTest::drupalGetWithAlert
    $event->dragTo($destination);
    // Wait for the alert to appear.
    $this->getSession()->getPage()->waitFor(10, function () {
      try {
        $driver = $this->getSession()->getDriver();
        assert($driver instanceof DrupalSelenium2Driver);
        $driver->getWebDriverSession()->getAlert_text();
        return TRUE;
      }
      catch (\Exception $e) {
        return FALSE;
      }
    });
    $driver = $this->getSession()->getDriver();
    assert($driver instanceof DrupalSelenium2Driver);
    $alert_text = $driver->getWebDriverSession()->getAlert_text();
    $this->assertEquals('Alert', $alert_text);
    $driver->getWebDriverSession()->accept_alert();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->createScreenshot('../calendar.png');

    $civicrm_api = $this->container->get('civicrm_entity.api');
    $activity = $civicrm_api->get('activity', [
      'id' => $this->activityId,
    ]);
  }

}
