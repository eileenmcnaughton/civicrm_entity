<?php

namespace Drupal\civicrm_entity_bundles\Controller;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_ui\FieldConfigListBuilder;

/**
 * A Field UI controller.
 */
final class FieldUiController implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * The Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundler info object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Shows the 'Manage fields' page for CiviCRM entities.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function fieldListing($entity_type_id) {
    $list_builder = $this->entityTypeManager->getListBuilder('field_config');
    assert($list_builder instanceof FieldConfigListBuilder);
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    // We replicate all field config across bundles, so take the first bundle
    // name.
    $bundle = key($bundles);

    // Field UI provides parameter overrides.
    return $list_builder->render($entity_type_id, $bundle);
  }

}
