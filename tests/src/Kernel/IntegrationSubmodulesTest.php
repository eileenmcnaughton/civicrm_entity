<?php

declare(strict_types=1);

namespace Drupal\Tests\civicrm_entity\Kernel;

/**
 * Tests optional integration submodule discovery on supported Drupal versions.
 *
 * @requires module ds
 * @requires module field_group
 * @requires module rules
 * @requires module search_api
 * @requires module typed_data
 * @requires module views_bulk_operations
 * @group civicrm_entity
 */
final class IntegrationSubmodulesTest extends CivicrmEntityTestBase {

  /**
   * Tests that integration classes load only after their modules are enabled.
   */
  public function testIntegrationSubmodules(): void {
    $integrations = [
      'civicrm_entity_vbo',
      'civicrm_entity_rules',
      'civicrm_entity_search_api',
      'civicrm_entity_field_group',
      'civicrm_entity_ds',
    ];

    $this->assertTrue(
      $this->container->get('module_installer')->install($integrations, TRUE)
    );
    foreach ($integrations as $integration) {
      $this->assertTrue($this->container->get('module_handler')->moduleExists($integration));
    }

    $definitions = $this->container->get('plugin.manager.action')->getDefinitions();
    $this->assertArrayHasKey('civicrm_contact_add_to_group', $definitions);
    $this->assertTrue($this->container->has('Drupal\\civicrm_entity_rules\\Hook\\RulesHooks'));
    $this->assertTrue($this->container->has('civicrm_entity_search_api.subscriber'));
    $this->assertTrue($this->container->has('Drupal\\civicrm_entity_field_group\\Hook\\BundleHooks'));
    $this->assertTrue($this->container->has('Drupal\\civicrm_entity_ds\\Hook\\BundleHooks'));
  }

}
