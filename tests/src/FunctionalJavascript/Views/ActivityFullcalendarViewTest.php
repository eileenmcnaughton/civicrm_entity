<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityTestBase;

/**
 * Test of Full Calendar Activity Views.
 *
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

  /**
   * Test Full Calendar Display.
   */
  public function testFullcalendarDisplay(): void {
    $this->drupalGet('/activity-fullcalendar');
    $this->createScreenshot('../calendar.png');
    $fullcalendar = $this->assertSession()->elementExists('css', '.js-drupal-fullcalendar');
    $this->assertSession()->waitForElement('css', '.fc-event-container');
    $this->assertSession()->elementTextContains('css', '.fc-event-container .fc-time', '12:00 pm');
    $this->assertSession()->elementTextContains('css', '.fc-event-container .fc-title', 'Meeting about new seeds');

    // @todo The dialog opened is extremely difficult to target.
    $this->click('.fc-event-container .fc-event');
    $this->assertSession()->waitForElement('css', '.jsframe-titlebar-focused');
    $this->assertSession()->elementTextContains('css', '.jsframe-titlebar-focused', 'Meeting about new seeds');

    $modal = $this->assertSession()->elementExists('css', 'div[id^="window_"][id$="_canvas"]');
    self::assertNotNull($modal);
    $this->assertSession()->elementTextContains('css', 'div[id^="window_"][id$="_canvas"]', 'Meeting about new seeds');
    $this->assertSession()->elementTextContains('css', 'div[id^="window_"][id$="_canvas"]', 'johnny');

    $civicrm_api = $this->container->get('civicrm_entity.api');
    $activity = $civicrm_api->get('activity', [
      'id' => $this->activityId,
    ]);

    $date = new DrupalDateTime($activity[$this->activityId]['activity_date_time']);
    $duration = $activity[$this->activityId]['duration'];
    $start_formatted = $date->format('D, m/d/Y - H:i');
    $date->add(new \DateInterval("PT{$duration}M"));
    $end_formatted = $date->format('D, m/d/Y - H:i');
    $this->assertSession()->pageTextContains($start_formatted . ' - ' . $end_formatted);
  }

  /**
   * Test Activity Drag and Update.
   */
  public function testActivityDragAndUpdate(): void {
    $previous_day = new DrupalDateTime('-1 day');
    $previous_day_formatted = $previous_day->format('Y-m-d');

    $this->drupalGet('/activity-fullcalendar');
    $fullcalendar = $this->assertSession()->elementExists('css', '.js-drupal-fullcalendar');
    $event = $this->assertSession()->waitForElement('css', '.fc-event-container .fc-event');
    $destination = $this->assertSession()->elementExists('css', ".fc-bg [data-date='$previous_day_formatted']", $fullcalendar);

    // Using dragTo causes exceptions due to an alert appearing during the
    // "drop" event.
    // $event->dragTo($destination);
    $driver = $this->getSession()->getDriver();
    assert($driver instanceof DrupalSelenium2Driver);
    $webdriver_session = $driver->getWebDriverSession();

    $webdriver_session->moveto([
      'element' => $webdriver_session->element('xpath', $event->getXpath())->getID(),
    ]);
    $webdriver_session->buttondown();
    $webdriver_session->moveto([
      'element' => $webdriver_session->element('xpath', $destination->getXpath())->getID(),
    ]);
    $webdriver_session->buttonup();

    $driver = $this->getSession()->getDriver();
    assert($driver instanceof DrupalSelenium2Driver);
    $alert_text = $driver->getWebDriverSession()->getAlert_text();
    // @todo deprecated in Drupal 9.2 PHPUnit bump.
    // Change to assertMatchesRegularExpression for PHPUnit 10.
    // @see https://www.drupal.org/project/drupal/issues/3217709
    $this->assertRegExp(
      '/^Meeting about new seeds start is now ([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]) (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9]) and end is now ([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]) (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9]) - Do you want to save this change\?$/',
      $alert_text
    );
    $driver->getWebDriverSession()->accept_alert();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $civicrm_api = $this->container->get('civicrm_entity.api');
    $activity = $civicrm_api->get('activity', [
      'id' => $this->activityId,
    ]);
    $this->assertEquals($previous_day_formatted . ' 12:00:00', $activity[$this->activityId]['activity_date_time']);
  }

}
