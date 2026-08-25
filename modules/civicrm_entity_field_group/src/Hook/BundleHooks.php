<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_field_group\Hook;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Entity hooks for the Field Group integration.
 */
final class BundleHooks {

  public function __construct(
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {}

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityDisplayInterface $display): void {
    $entity_type = $entity->getEntityType();
    if (!$entity_type->get('civicrm_entity') || !$entity_type->hasKey('bundle')) {
      return;
    }

    $view_mode_ids = array_keys($this->entityDisplayRepository->getViewModeOptions($entity_type->id()));
    $context = [
      'entity_type' => $display->getTargetEntityTypeId(),
      'bundle' => $entity_type->id(),
      'entity' => $entity,
      'display_context' => 'view',
      'mode' => in_array($display->getMode(), $view_mode_ids, TRUE)
        ? $display->getMode()
        : EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE,
    ];

    field_group_attach_groups($build, $context);
  }

}
