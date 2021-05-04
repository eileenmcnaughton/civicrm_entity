<?php

namespace Drupal\civicrm_entity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_ui\FieldConfigListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FieldUiController implements ContainerInjectionInterface {

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

  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Shows the 'Manage fields' page for CiviCRM entities.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
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
