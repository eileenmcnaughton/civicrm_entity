<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

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

}
