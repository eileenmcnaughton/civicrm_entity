<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

final class CivicrmActivityViewsTest extends CivicrmEntityViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $civicrmEntityTypeId = 'civicrm_activity';

  /**
   * {@inheritdoc}
   */
  public function testAddWizardValues() {
    parent::testAddWizardValues();
    // Specific bundles are present.
    $this->assertSession()->optionExists('show[type]', 'meeting');
    $this->assertSession()->optionExists('show[type]', 'email');
  }

  /**
   * {@inheritdoc}
   */
  protected function createSampleData() {
    // TODO: Implement createSampleData() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetupCreateView() {
    // TODO: Implement addAndConfigureFields() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCreateViewResults() {
    // @todo write tests.
    $this->doesNotPerformAssertions();
  }

}
