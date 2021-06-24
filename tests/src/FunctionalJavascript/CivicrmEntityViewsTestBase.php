<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Url;

abstract class CivicrmEntityViewsTestBase extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
  ];

  /**
   * The tested entity type.
   *
   * Tests must specify a value for the test to run properly.
   *
   * @var string
   */
  protected static $civicrmEntityTypeId = NULL;

  /**
   * The entity permissions.
   *
   * Permissions required to view the entity type.
   *
   * @var string[]
   */
  protected static $civicrmEntityPermissions = [];

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->createUser([
      'access content',
      'administer civicrm entity',
      'administer views',
    ] + static::$civicrmEntityPermissions);
    $this->drupalLogin($admin_user);
    $this->enableCivicrmEntityTypes([static::$civicrmEntityTypeId]);

    // Generate the sample data.
    $this->createSampleData();

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
    $this->createNewView();
    $this->doSetupCreateView();
    $this->getSession()->getPage()->pressButton('Save');
    $this->drupalGet('/' . static::$civicrmEntityTypeId);
    $this->htmlOutput();
    $this->assertCreateViewResults();
  }

  /**
   * Tests creating a view with relationships for the entity type.
   *
   * @group debug
   */
  public function testViewWithRelationships() {
    $this->createNewView();
    $this->doSetupViewWithRelationships();
    $this->getSession()->getPage()->pressButton('Save');
    $this->drupalGet('/' . static::$civicrmEntityTypeId);
    $this->htmlOutput();
    $this->assertViewWithRelationshipsResults();
  }

  // @todo testCreateViewWithFilters()
  // @todo testCreateViewWithSorts()
  // @todo testCreateViewWithRelationships()


  /**
   * Creates sample data for each test.
   *
   * @return void
   */
  abstract protected function createSampleData();

  /**
   * Runs setup for the ::testCreateView test.
   *
   * @return void
   */
  abstract protected function doSetupCreateView();

  /**
   * Runs assertions for the ::testCreateView test.
   *
   * @return void
   */
  abstract protected function assertCreateViewResults();

  /**
   * Runs setup for the ::testViewWithRelationships test.
   *
   * @return void
   */
  abstract protected function doSetupViewWithRelationships();

  /**
   * Runs assertions for the ::testViewWithRelationships test.
   *
   * @return void
   */
  abstract protected function assertViewWithRelationshipsResults();

  /**
   * Creates a new View for the tested entity type.
   *
   * The test lands on the Edit form for the View.
   */
  protected function createNewView() {
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
  }

  /**
   * Adds a field to a Views display.
   *
   * @param string $name_locator
   *   The field's checkbox locator
   * @param array $configuration
   *   The field's display configuration.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function addFieldToDisplay(string $name_locator, array $configuration = []) {
    $this->clickAjaxLink('views-add-field');
    $this->htmlOutput();
    $this->getSession()->getPage()->checkField($name_locator);
    $this->submitViewsDialog();
    foreach ($configuration as $field_name => $value) {
      $field = $this->assertSession()->fieldExists($field_name);
      $field->setValue($value);
    }
    $this->submitViewsDialog();
  }

  protected function addRelationshipToDisplay(string $name_locator, array $configuration = []) {
    $this->clickAjaxLink('views-add-relationship');
    $this->getSession()->getPage()->checkField($name_locator);
    $this->submitViewsDialog();
    foreach ($configuration as $field_name => $value) {
      $field = $this->assertSession()->fieldExists($field_name);
      $field->setValue($value);
    }
    $this->submitViewsDialog();
  }

  /**
   * Submits a dialog when editing a View.
   */
  protected function submitViewsDialog(): void {
    $button = $this->assertSession()->waitForElementVisible('css', '.views-ui-dialog button[type="button"].button--primary');
    $this->assertNotEmpty($button);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks an AJAX link with specified locator.
   *
   * @param string $locator
   *    The link id, title, text or image alt.
   *
   * @throws ElementNotFoundException
   */
  protected function clickAjaxLink(string $locator): void {
    $this->getSession()->getPage()->clickLink($locator);
    $this->assertSession()->assertWaitOnAjaxRequest();
  }


}
