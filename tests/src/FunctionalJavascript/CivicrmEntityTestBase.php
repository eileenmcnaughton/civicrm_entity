<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\civicrm\FunctionalJavascript\CiviCrmTestBase;

/**
 * Base class for CiviCRM Entity tests.
 */
abstract class CivicrmEntityTestBase extends CiviCrmTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'civicrm_entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Enable CiviCRM Entity types.
   *
   * @param array $entity_types
   *   The entity type Ids.
   */
  protected function enableCivicrmEntityTypes(array $entity_types): void {
    $this->drupalGet(Url::fromRoute('civicrm_entity.settings'));
    $page = $this->getSession()->getPage();
    foreach ($entity_types as $entity_type) {
      $page->checkField("enabled_entity_types[$entity_type][enabled]");
    }
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
