<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Url;

abstract class CivicrmEntityViewsTestBase extends CivicrmEntityTestBase {

  protected static $modules = [
    'views',
    'views_ui',
  ];

  protected static $civicrmEntityTypeId = NULL;

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->createUser([
      'access content',
      'administer civicrm entity',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes([static::$civicrmEntityTypeId]);
  }

  /**
   * Tests the ViewAddForm for this entity.
   */
  public function testAddWizardValues() {
    $supported_entities = SupportedEntities::getInfo();
    $civicrm_entity_info = $supported_entities[static::$civicrmEntityTypeId];
    $this->drupalGet(Url::fromRoute('views_ui.add'));

    $page = $this->getSession()->getPage();
    $page->fillField('label', static::$civicrmEntityTypeId . ' view');
    $this->assertJsCondition('jQuery("#edit-label-machine-name-suffix .machine-name-value").html() !== ""');

    $page->selectFieldOption('show[wizard_key]', 'standard:' . static::$civicrmEntityTypeId);
    $this->assertSession()->assertWaitOnAjaxRequest();
    if (!empty($civicrm_entity_info['bundle property'])) {
      $this->assertSession()->optionExists('show[type]', static::$civicrmEntityTypeId);
    }
    else {
      $this->assertSession()->fieldNotExists('show[type]');
    }
  }

  /**
   * Tests creating a basic view with the entity type.
   * @group debug
   */
  public function testCreateView() {
    $this->createSampleData();

    $this->drupalGet(Url::fromRoute('views_ui.add'));
    $page = $this->getSession()->getPage();
    $page->fillField('label', static::$civicrmEntityTypeId . ' view');
    $this->assertJsCondition('jQuery("#edit-label-machine-name-suffix .machine-name-value").html() !== ""');
    $page->selectFieldOption('show[wizard_key]', 'standard:' . static::$civicrmEntityTypeId);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->checkField('page[create]');
    $page->fillField('page[path]', '/' . static::$civicrmEntityTypeId);
    $page->pressButton('Save and edit');
    $this->assertSession()->pageTextContains('The view ' . static::$civicrmEntityTypeId . ' view has been saved.');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->htmlOutput();
    $page->clickLink('views-add-field');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->addAndConfigureFields();

    $this->drupalGet('/' . static::$civicrmEntityTypeId);
    $this->assertCreateViewResults();
  }

  // @todo testCreateViewWithFilters()
  // @todo testCreateViewWithSorts()
  // @todo testCreateViewWithRelationships()

  protected abstract function createSampleData();

  protected abstract function addAndConfigureFields();

  protected function submitViewsDialog() {
    $button = $this->assertSession()->waitForElementVisible('css', '.views-ui-dialog button[type="button"].button--primary');
    $this->assertNotEmpty($button);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  abstract protected function assertCreateViewResults();

}
