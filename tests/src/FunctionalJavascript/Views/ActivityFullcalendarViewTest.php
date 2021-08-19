<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityTestBase;

/**
 * @requires module fullcalendar_view
 */
final class ActivityFullcalendarViewTest extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fullcalendar_view',
  ];

  /**
   * {@inheritdoc}
   *
   * @todo needs more data.
   */
  public function setUp(): void {
    parent::setUp();
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

}
