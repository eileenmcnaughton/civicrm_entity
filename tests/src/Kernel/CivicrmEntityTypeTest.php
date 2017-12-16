<?php

namespace Drupal\Tests\civicrim_entity\Kernel;

use Drupal\KernelTests\KernelTestBase;

class CivicrmEntityTypeTest extends KernelTestBase {
  protected static $modules = [
    'civicrm',
    'civicrm_entity',
    'field',
    'text',
    'options',
    'link',
  ];

  protected function setUp() {
    parent::setUp();
    require __DIR__ . '/../Type.php';
  }

  public function testEntityType() {
    $definition = $this->container->get('entity_type.manager')->getDefinition('civicrm_event');

    $keys = $definition->getKeys();
    $this->assertEquals('id', $keys['id']);
    $this->assertEquals('title', $keys['label']);

    $links = $definition->getLinkTemplates();
    $this->assertEquals('/civicrm-event/{civicrm_event}', $links['canonical']);
    $this->assertEquals('/admin/structure/civicrm-entity/civicrm-event/{civicrm_event}/edit', $links['edit-form']);
    $this->assertEquals('/admin/structure/civicrm-entity/civicrm-event', $links['collection']);
  }

}
