<?php declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript\Views;

use Drupal\Tests\civicrm_entity\FunctionalJavascript\CivicrmEntityViewsTestBase;

final class CivicrmActivityViewsTest extends CivicrmEntityViewsTestBase {

  protected static $civicrmEntityType = 'civicrm_activity';

  public function testAddWizardValues() {
    parent::testAddWizardValues();
    // Specific bundles are present.
    $this->assertSession()->optionExists('show[type]', 'meeting');
    $this->assertSession()->optionExists('show[type]', 'email');
  }

  protected function createSampleData() {
    // TODO: Implement createSampleData() method.
  }

  protected function doSetupCreateView() {
    // TODO: Implement addAndConfigureFields() method.
  }

  protected function assertCreateViewResults() {
    // TODO: Implement assertCreateViewResults() method.
  }

}
