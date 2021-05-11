<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Drupal\Core\Url;

final class CivicrmEntityViewsTest extends CivicrmEntityTestBase {

  protected static $modules = [
    'views',
    'views_ui',
  ];

  public function testConfigureSource() {
    $admin_user = $this->createUser([
      'administer civicrm entity',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes(['civicrm_event', 'civicrm_activity']);
    $this->drupalGet(Url::fromRoute('views_ui.add'));

    $page = $this->getSession()->getPage();
    $page->fillField('label', 'event view');

    $page->selectFieldOption('show[wizard_key]', 'standard:civicrm_event');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // All
    $this->assertSession()->optionExists('show[type]', 'civicrm_event');
    // Specifics
    $this->assertSession()->optionExists('show[type]', 'conference');
    $this->assertSession()->optionExists('show[type]', 'workshop');

    $page->selectFieldOption('show[wizard_key]', 'standard:civicrm_activity');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // All
    $this->assertSession()->optionExists('show[type]', 'civicrm_activity');
    // Specifics
    $this->assertSession()->optionExists('show[type]', 'meeting');
    $this->assertSession()->optionExists('show[type]', 'email');
  }

}
