<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Hook implementations for entity alters.
 */
class EntityAlterHooks {

  /**
   * Constructor for EntityHooks.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_entity_view_display_alter().
   *
   * There is no way to handle this in the entity type's view build.
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    $entity_type = $this->entityTypeManager->getDefinition($context['entity_type']);
    assert($entity_type !== NULL);
    if ($entity_type->get('civicrm_entity') && $entity_type->hasKey('bundle')) {
      $entity_display_repository = $this->entityDisplayRepository;
      assert($entity_display_repository instanceof EntityDisplayRepositoryInterface);
      $entity_view_mode_ids = array_keys($entity_display_repository->getViewModeOptions($entity_type->id()));
      $view_mode = !empty($context['view_mode']) && in_array($context['view_mode'], $entity_view_mode_ids) ? $context['view_mode'] : $entity_display_repository::DEFAULT_DISPLAY_MODE;
      $root_display = $entity_display_repository->getViewDisplay(
        $entity_type->id(),
        $entity_type->id(),
        $view_mode
      );
      $display->set('content', $root_display->get('content'));
      $display->set('hidden', $root_display->get('hidden'));

      if ($root_display instanceof LayoutBuilderEntityViewDisplay) {
        $layout_builder_settings = $root_display->getThirdPartySettings('layout_builder');
        foreach ($layout_builder_settings as $setting_key => $setting) {
          $display->setThirdPartySetting('layout_builder', $setting_key, $setting);
        }
      }
      $ds_settings = $root_display->getThirdPartySettings('ds');
      if (!empty($ds_settings) && is_array($ds_settings)) {
        foreach ($ds_settings as $setting_key => $setting) {
          $display->setThirdPartySetting('ds', $setting_key, $setting);
        }
      }
    }
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityDisplayInterface $display): void {
    $entity_type = $entity->getEntityType();
    if ($entity_type->get('civicrm_entity') && $entity_type->hasKey('bundle') && $this->moduleHandler->moduleExists('field_group')) {
      $entity_display_repository = $this->entityDisplayRepository;
      $entity_view_mode_ids = array_keys($entity_display_repository->getViewModeOptions($entity_type->id()));

      $context = [
        'entity_type' => $display->getTargetEntityTypeId(),
        'bundle' => $entity_type->id(),
        'entity' => $entity,
        'display_context' => 'view',
        'mode' => in_array($display->getMode(), $entity_view_mode_ids) ? $display->getMode() : $entity_display_repository::DEFAULT_DISPLAY_MODE,
      ];

      field_group_attach_groups($build, $context);
    }
  }

}
