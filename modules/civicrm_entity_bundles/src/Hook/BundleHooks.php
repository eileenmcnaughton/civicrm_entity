<?php

namespace Drupal\civicrm_entity_bundles\Hook;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\civicrm_entity_bundles\Plugin\Field\BundleFieldItemList;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Hook implementations for CiviCRM Entity bundle support.
 */
class BundleHooks {

  /**
   * Constructor for BundleHooks.
   */
  public function __construct(
    protected CiviCrmApiInterface $civicrmApi,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * Sets the bundle key and bundle property on CiviCRM entity types that have a
   * 'bundle property' defined in SupportedEntities::getInfo(). Also updates the
   * add-form link template to include the bundle parameter.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    $supported_entities = SupportedEntities::getInfo();
    foreach ($supported_entities as $entity_type_id => $civicrm_entity_info) {
      if (empty($civicrm_entity_info['bundle property'])) {
        continue;
      }
      if (!isset($entity_types[$entity_type_id])) {
        continue;
      }
      $entity_type = $entity_types[$entity_type_id];
      $entity_type->set('entity_keys', array_replace($entity_type->getKeys(), ['bundle' => 'bundle']));
      $entity_type->set('civicrm_bundle_property', $civicrm_entity_info['bundle property']);
      if ($entity_type->hasLinkTemplate('add-form')) {
        $add_form = $entity_type->getLinkTemplate('add-form');
        $entity_type->setLinkTemplate('add-page', $add_form);
        $entity_type->setLinkTemplate('add-form', sprintf('%s/{%s}', $add_form, $entity_type->getKey('bundle')));
      }
    }
  }

  /**
   * Implements hook_entity_base_field_info().
   *
   * Adds the computed bundle field to CiviCRM entity types that have bundle
   * support enabled. Injecting the field here (rather than in baseFieldDefinitions)
   * ensures it is added after all other base fields, avoiding default-value
   * conflicts during entity initialization.
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    $fields = [];
    if ($entity_type->get('civicrm_entity') && $entity_type->hasKey('bundle')) {
      $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('string')
        ->setLabel($entity_type->getBundleLabel())
        ->setRequired(TRUE)
        ->setReadOnly(TRUE)
        ->setComputed(TRUE)
        ->setClass(BundleFieldItemList::class);
    }
    return $fields;
  }

  /**
   * Implements hook_entity_bundle_info().
   */
  #[Hook('entity_bundle_info')]
  public function entityBundleInfo(): array {
    $transliteration = \Drupal::transliteration();

    $bundles = [];
    $entity_types_with_bundles = array_filter(SupportedEntities::getInfo(), static function (array $civicrm_entity_info) {
      return !empty($civicrm_entity_info['bundle property']);
    });
    foreach ($entity_types_with_bundles as $entity_type_id => $civicrm_entity_info) {
      // Keep a bundle that is the same as the entity type ID so fields can be
      // created as if the entity has no bundles.
      $bundles[$entity_type_id] = [
        $entity_type_id => [
          'label' => $civicrm_entity_info['civicrm entity label'],
        ],
      ];
      $options = $this->civicrmApi->getOptions($civicrm_entity_info['civicrm entity name'], $civicrm_entity_info['bundle property']);
      foreach ($options as $option) {
        $machine_name = SupportedEntities::optionToMachineName($option, $transliteration);
        $bundles[$entity_type_id][$machine_name]['label'] = $option;
      }
    }
    return $bundles;
  }

  /**
   * Implements hook_entity_bundle_field_info().
   *
   * Ensures CiviCRM Entity types have field config instances across all bundles
   * by cloning field configs from the root bundle. Also creates BaseFieldOverrides
   * for any base fields not yet present.
   *
   * @see field_entity_bundle_field_info()
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $result = [];
    if ($entity_type->get('civicrm_entity_ui_exposed') && $entity_type->hasKey('bundle')) {
      $ids = $this->entityTypeManager
        ->getStorage('field_config')
        ->getQuery()
        ->condition('id', $entity_type->id() . '.', 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->execute();
      $field_configs = FieldConfig::loadMultiple($ids);
      $cloned_field_configs = array_map(static function (FieldConfigInterface $field) use ($bundle) {
        $cloned = clone $field;
        $cloned->set('bundle', $bundle);
        return $cloned;
      }, $field_configs);
      foreach ($cloned_field_configs as $field_instance) {
        $result[$field_instance->getName()] = $field_instance;
      }
    }
    if ($entity_type->get('civicrm_entity_ui_exposed') && $entity_type->hasKey('bundle')) {
      foreach ($base_field_definitions as $field_name => $definition) {
        if (isset($result[$field_name]) || empty($bundle)) {
          continue;
        }
        $field = BaseFieldOverride::createFromBaseFieldDefinition($definition, $bundle);
        $result[$field_name] = $field;
      }
    }
    return $result;
  }

  /**
   * Implements hook_entity_view_display_alter().
   *
   * Redirects bundle view display content/settings to the root bundle display.
   * There is no way to handle this in the entity type's view builder.
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

      if ($this->moduleHandler->moduleExists('layout_builder') && $root_display instanceof LayoutBuilderEntityViewDisplay) {
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
   *
   * Attaches field_group groups from the root bundle display context.
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
