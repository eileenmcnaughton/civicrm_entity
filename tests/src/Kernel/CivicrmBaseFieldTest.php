<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\Entity\CivicrmEntity;

/**
 * Tests base field generation.
 *
 * @group civicrim_entity
 */
class CivicrmBaseFieldTest extends CivicrmEntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'civicrm',
    'civicrm_entity',
    'field',
    'text',
    'options',
    'link',
  ];

  /**
   * Tests the base fields generated.
   */
  public function testBaseFields() {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $base_fields */
    $base_fields = CivicrmEntity::baseFieldDefinitions($this->container->get('entity_type.manager')->getDefinition('civicrm_contact'));

    $this->assertTrue(isset($base_fields['id']));
    $this->assertEquals('integer', $base_fields['id']->getType());
    $this->assertTrue(isset($base_fields['title']));
    $this->assertEquals('string', $base_fields['title']->getType());
    $this->assertTrue(isset($base_fields['phone_number']));
    $this->assertEquals('string', $base_fields['phone_number']->getType());
    $this->assertTrue(isset($base_fields['birth_date']));
    $this->assertEquals('datetime', $base_fields['birth_date']->getType());
    $this->assertTrue(isset($base_fields['activity_date_time']));
    $this->assertEquals('datetime', $base_fields['activity_date_time']->getType());
    $this->assertTrue(isset($base_fields['is_auto']));
    $this->assertEquals('boolean', $base_fields['is_auto']->getType());
    $this->assertTrue(isset($base_fields['details']));
    $this->assertEquals('text_long', $base_fields['details']->getType());
    $this->assertTrue(isset($base_fields['refresh_date']));
    $this->assertEquals('timestamp', $base_fields['refresh_date']->getType());
  }

}
