<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_ds\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * Entity hooks for the Display Suite integration.
 */
final class BundleHooks {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {}

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter', order: new OrderAfter(['civicrm_entity_bundles']))]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    $entity_type = $this->entityTypeManager->getDefinition($context['entity_type'], FALSE);
    if (!$entity_type || !$entity_type->get('civicrm_entity') || !$entity_type->hasKey('bundle')) {
      return;
    }

    $view_mode_ids = array_keys($this->entityDisplayRepository->getViewModeOptions($entity_type->id()));
    $view_mode = !empty($context['view_mode']) && in_array($context['view_mode'], $view_mode_ids, TRUE)
      ? $context['view_mode']
      : EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE;
    $root_display = $this->entityDisplayRepository->getViewDisplay(
      $entity_type->id(),
      $entity_type->id(),
      $view_mode,
    );

    foreach ($root_display->getThirdPartySettings('ds') as $setting_key => $setting) {
      $display->setThirdPartySetting('ds', $setting_key, $setting);
    }
  }

}
