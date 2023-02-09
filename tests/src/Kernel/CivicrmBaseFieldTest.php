<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

/**
 * Tests base field generation.
 *
 * @group civicrim_entity
 */
class CivicrmBaseFieldTest extends CivicrmEntityTestBase {

  /**
   * Tests the base fields generated.
   */
  public function testBaseFields() {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $base_fields */
    $base_fields = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('civicrm_contact');

    $this->assertTrue(isset($base_fields['id']));
    $this->assertEquals('integer', $base_fields['id']->getType());
    $this->assertEquals('civicrm_contact', $base_fields['id']->getTargetEntityTypeId());
    $this->assertTrue(isset($base_fields['display_name']));
    $this->assertEquals('string', $base_fields['display_name']->getType());
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
    $this->assertEquals('datetime', $base_fields['refresh_date']->getType());
    $this->assertTrue(isset($base_fields['primary_contact_id']));
    $this->assertEquals('entity_reference', $base_fields['primary_contact_id']->getType());
    $this->assertEquals('civicrm_contact', $base_fields['primary_contact_id']->getSetting('target_type'));
    $this->assertTrue(isset($base_fields['msg_template_id']));
    $this->assertEquals('integer', $base_fields['msg_template_id']->getType());

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $base_fields */
    $base_fields = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('civicrm_address');
    $this->assertTrue(isset($base_fields['contact_id']));
    $this->assertEquals('entity_reference', $base_fields['contact_id']->getType());
    $this->assertEquals('civicrm_contact', $base_fields['contact_id']->getSetting('target_type'));
    $this->assertEquals('civicrm_address', $base_fields['contact_id']->getTargetEntityTypeId());
  }

}
