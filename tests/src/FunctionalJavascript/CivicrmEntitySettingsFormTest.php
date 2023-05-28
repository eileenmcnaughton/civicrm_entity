<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the settings form for the module.
 *
 * @group civicrm_entity
 */
final class CivicrmEntitySettingsFormTest extends CivicrmEntityTestBase {

  /**
   * Tests enabling entity types.
   */
  public function testEnableNewEntityTypes() {
    $admin_user = $this->createUser([
      'administer civicrm entity',
    ]);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes(['civicrm_event', 'civicrm_activity']);
    $this->drupalGet(Url::fromRoute('civicrm_entity.admin'));
    $this->assertSession()->linkExists('CiviCRM Activity');
    $this->assertSession()->linkExists('CiviCRM Event');

    $this->drupalGet(Url::fromRoute('civicrm_entity.settings'));
    $page = $this->getSession()->getPage();
    foreach (['civicrm_contact'] as $entity_type) {
      $page->checkField("enabled_entity_types[$entity_type][enabled]");
      $page->uncheckField("enabled_entity_types[$entity_type][enable_links][view]");
      $page->uncheckField("enabled_entity_types[$entity_type][enable_links][add]");
      $page->uncheckField("enabled_entity_types[$entity_type][enable_links][edit]");
      $page->uncheckField("enabled_entity_types[$entity_type][enable_links][delete]");
    }
    $page->pressButton('Save configuration');
    $this->drupalGet('/civicrm-contact/add');
    $this->assertSession()->responseContains('Page not found');
    $this->drupalGet('/civicrm-contact/1');
    $this->assertSession()->responseContains('Page not found');
    $this->drupalGet('/civicrm-contact/1/edit');
    $this->assertSession()->responseContains('Page not found');
    $this->drupalGet('/civicrm-contact/1/delete');
    $this->assertSession()->responseContains('Page not found');
  }

  /**
   * Tests that the filter can be configured.
   */
  public function testConfigureDefaultFilterFormat() {
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em>',
          ],
        ],
      ],
    ]);
    $basic_html_format->save();

    $admin_user = $this->createUser([
      'administer filters',
      'administer civicrm entity',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('civicrm_entity.admin'));
    $this->clickLink('Settings');
    $this->getSession()->getPage()->selectFieldOption('filter_format', $basic_html_format->id());
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
