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
class CivicrmEntityLocalAction extends DeriverBase implements ContainerDeriverInterface {

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
      return $type->getProvider() === 'civicrm_entity' && $type->get('civicrm_entity_ui_exposed');
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($civicrm_entities as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('add-form')) {
        $this->derivatives["civicrm_entity_add_$entity_type_id"] = [
          'route_name' => "entity.$entity_type_id.add_form",
          'title' => $this->t('Add :label', [':label' => $entity_type->getLabel()]),
          'appears_on' => ["entity.$entity_type_id.collection"],
        ] + $base_plugin_definition;

        if ($entity_type->hasKey('bundle')) {
          $this->derivatives["civicrm_entity_add_$entity_type_id"]['route_parameters'] = [
            $entity_type->getKey('bundle') => $entity_type_id,
          ];
        }
      }
    }

    return $this->derivatives;
  }

}
