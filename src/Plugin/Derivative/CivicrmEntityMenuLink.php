<?php

namespace Drupal\civicrm_entity\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions for all CiviCRM entity definitions.
 */
class CivicrmEntityMenuLink extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CivicrmEntityLocalAction object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $civicrm_entities = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $type->getProvider() == 'civicrm_entity' && $type->get('civicrm_entity_ui_exposed');
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($civicrm_entities as $entity_type_id => $entity_type) {
      $this->derivatives["field_storage_config_add_$entity_type_id"] = [
        'route_name' => "entity.$entity_type_id.collection",
        'title' => $entity_type->getLabel(),
        'parent' => 'civicrm_entity.admin',
        'description' => $this->t('Manage :label', [':label' => $entity_type->getLabel()]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
