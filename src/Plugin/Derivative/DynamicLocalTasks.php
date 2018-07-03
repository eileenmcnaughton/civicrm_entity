<?php

namespace Drupal\civicrm_entity\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates moderation-related local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an FieldUiLocalTask object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->basePluginId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation')
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

    foreach ($civicrm_entities as $entity_type_id => $entity_type) {
      $this->derivatives["$entity_type_id.canonical"] = [
        'route_name' => "entity.$entity_type_id.canonical",
        'title' => $this->t('View'),
        'base_route' => "entity.$entity_type_id.canonical",
      ] + $base_plugin_definition;
      $this->derivatives["$entity_type_id.edit_form"] = [
        'route_name' => "entity.$entity_type_id.edit_form",
        'title' => $this->t('Edit'),
        'base_route' => "entity.$entity_type_id.canonical",
      ] + $base_plugin_definition;
      $this->derivatives["entity.$entity_type_id.collection"] = [
        'route_name' => "entity.$entity_type_id.collection",
        'title' => $this->t('List'),
        'base_route' => "entity.$entity_type_id.collection",
        'weight' => -10,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
