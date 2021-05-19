<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

abstract class CivicrmEntityViewsTestBase extends CivicrmEntityTestBase {

  protected static $modules = [
    'views',
    'views_ui',
  ];

  protected static $civicrmEntityTypeId = NULL;

  protected static $civicrmEntityPermissions = [];

  protected function doInstall() {
    parent::doInstall();

    // The database information was added inside of our test environment,
    // but it wasn't added to the Drupal settings to make it available for
    // directly queries via Views.
    // @todo This needs to be documented for all users wanting Views integration.
    // @todo There is a workaround in CivicrmSql::init, that is why this is
    //   commented out. This way we can test the workaround.
    // @see \Drupal\Core\Installer\Form\SiteSettingsForm::submitForm
    // @see \Drupal\Tests\civicrm\FunctionalJavascript\CiviCrmTestBase::changeDatabasePrefix
    // @see \Drupal\civicrm_entity\Plugin\views\query\CivicrmSql::init
    /*
    $connection = Database::getConnection('default', 'civicrm_test')->getConnectionOptions();
    $settings['databases']['civicrm_test']['default'] = (object) [
      'value'    => [
        'driver' => $connection['driver'],
        'username' => $connection['username'],
        'password' => $connection['password'],
        'host' => $connection['host'],
        'database' => $connection['database'],
        'namespace' => $connection['namespace'],
        'port' => $connection['port'],
        // CiviCRM does not use prefixes.
        'prefix' => '',
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    */
  }

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->createUser([
      'access content',
      'administer civicrm entity',
      'administer views',
    ] + static::$civicrmEntityPermissions);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes([static::$civicrmEntityTypeId]);

    // Disable automatic live preview to make the sequence of calls clearer. And
    // prevent errors on saving the view with the preview ajax load that are
    // cancelled.
    \Drupal::configFactory()
      ->getEditable('views.settings')
      ->set('ui.always_live_preview', FALSE)
      ->save();
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
    $this->doSetupCreateView();
    $this->htmlOutput();
    $this->drupalGet('/' . static::$civicrmEntityTypeId);
    $this->htmlOutput();
    $this->assertCreateViewResults();
  }

  // @todo testCreateViewWithFilters()
  // @todo testCreateViewWithSorts()
  // @todo testCreateViewWithRelationships()

  abstract protected function createSampleData();

  abstract protected function doSetupCreateView();
  abstract protected function assertCreateViewResults();

  protected function addFieldToDisplay(string $name_locator, array $configuration = []) {
    $this->clickAjaxLink('views-add-field');
    $this->getSession()->getPage()->checkField($name_locator);
    $this->submitViewsDialog();
    // @todo process configuration.
    $this->submitViewsDialog();
  }

  protected function submitViewsDialog() {
    $button = $this->assertSession()->waitForElementVisible('css', '.views-ui-dialog button[type="button"].button--primary');
    $this->assertNotEmpty($button);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks link with specified locator.
   *
   * @param string $locator
   *    The link id, title, text or image alt.
   *
   * @throws ElementNotFoundException
   */
  protected function clickAjaxLink(string $locator) {
    $this->getSession()->getPage()->clickLink($locator);
    $this->assertSession()->assertWaitOnAjaxRequest();
  }


}
