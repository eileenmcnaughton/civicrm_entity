<?php

namespace Drupal\civicrm_entity\Plugin\InlineForm;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the inline form handler for CiviCRM entities.
 *
 * @InlineForm(
 *   id = "civicrm_entity",
 *   label = @Translation("CiviCRM Entity"),
 *   entity_type = "civicrm_entity"
 * )
 */
class CivicrmEntityInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('theme.manager')
    );
  }

}
