<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\Entity\ContentUninstallValidator;
use Drupal\civicrm_entity\ModuleInstaller as LegacyModuleInstaller;
use Drupal\civicrm_entity_bundles\Plugin\SectionStorage\CivicrmEntityBundlesDefaultsSectionStorage;

/**
 * Tests entity definition.
 *
 * @group civicrim_entity
 */
class CivicrmEntityTypeTest extends CivicrmEntityTestBase {

  /**
   * Tests the generated entity type.
   */
  public function testEntityType() {
    $definition = $this->container->get('entity_type.manager')->getDefinition('civicrm_event');

    $keys = $definition->getKeys();
    $this->assertEquals('id', $keys['id']);
    $this->assertEquals('title', $keys['label']);

    $links = $definition->getLinkTemplates();
    $this->assertEquals('/civicrm-event/{civicrm_event}', $links['canonical']);
    $this->assertEquals('/civicrm-event/{civicrm_event}/edit', $links['edit-form']);
    $this->assertEquals('/admin/structure/civicrm-entity/civicrm-event', $links['collection']);
  }

  /**
   * Tests that the base module does not discover optional integration plugins.
   */
  public function testOptionalPluginsAreIsolated(): void {
    $definitions = $this->container->get('plugin.manager.action')->getDefinitions();
    $this->assertArrayNotHasKey('civicrm_contact_add_to_group', $definitions);
  }

  /**
   * Tests Drupal core compatibility metadata and uninstall validation.
   */
  public function testDrupalCoreCompatibility(): void {
    $bundles_info = $this->container
      ->get('extension.list.module')
      ->getExtensionInfo('civicrm_entity_bundles');
    $this->assertEmpty($bundles_info['core_incompatible']);

    $validator = $this->container->get('content_uninstall_validator');
    $this->assertInstanceOf(ContentUninstallValidator::class, $validator);
    $this->assertSame([], $validator->validate('civicrm_entity'));
    $this->assertNotInstanceOf(
      LegacyModuleInstaller::class,
      $this->container->get('module_installer'),
    );
  }

  /**
   * Tests the Layout Builder section storage replacement.
   */
  public function testLayoutBuilderSectionStorageCompatibility(): void {
    $this->assertTrue(
      $this->container->get('module_installer')->install(['layout_builder'], TRUE),
    );

    $definition = $this->container
      ->get('plugin.manager.layout_builder.section_storage')
      ->getDefinition('defaults');
    $this->assertSame(
      CivicrmEntityBundlesDefaultsSectionStorage::class,
      $definition->getClass(),
    );
  }

}
