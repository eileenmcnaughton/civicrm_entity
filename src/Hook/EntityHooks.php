<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm_entity\CivicrmEntityAccessHandler;
use Drupal\civicrm_entity\CivicrmEntityListBuilder;
use Drupal\civicrm_entity\CiviCrmEntityViewBuilder;
use Drupal\civicrm_entity\CivicrmEntityViewsData;
use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\civicrm_entity\Entity\Sql\CivicrmEntityStorageSchema;
use Drupal\civicrm_entity\Form\CivicrmEntityForm;
use Drupal\civicrm_entity\Routing\CiviCrmEntityRouteProvider;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Hook implementations for entities.
 */
class EntityHooks {

  /**
   * Constructor for EntityHooks.
   */
  public function __construct(
    protected CiviCrmApiInterface $civicrmApi,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_entity_type_build().
   *
   * Populates supported CiviCRM Entity definitions.
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types): void {
    $supported_entities = SupportedEntities::getInfo();
    $config = \Drupal::config('civicrm_entity.settings');
    $enabled_entity_types = $config->get('enabled_entity_types') ?: [];
    $enable_links_per_type = $config->get('enable_links_per_type') ?: [];
    foreach ($supported_entities as $entity_type_id => $civicrm_entity_info) {
      $clean_entity_type_id = str_replace('_', '-', $entity_type_id);
      $civicrm_entity_name = $civicrm_entity_info['civicrm entity name'];

      if (empty($civicrm_entity_info['label property'])) {
        \Drupal::logger('civicrm_entity')->debug(sprintf('Missing label property: %s', $entity_type_id));
        continue;
      }

      $entity_type_info = [
        'provider' => 'civicrm_entity',
        'class' => CivicrmEntity::class,
        'originalClass' => CivicrmEntity::class,
        'id' => $entity_type_id,
        'component' => $civicrm_entity_info['component'] ?? NULL,
        'civicrm_entity' => $civicrm_entity_name,
        'civicrm_entity_ui_exposed' => in_array($entity_type_id, $enabled_entity_types),
        'label' => new TranslatableMarkup('CiviCRM :name', [':name' => $civicrm_entity_info['civicrm entity label']]),
        // @todo add label_singular
        // @todo add label_plural
        // @todo add label_count
        'entity_keys' => [
          'id' => 'id',
          'label' => $civicrm_entity_info['label property'],
        ],
        'base_table' => $civicrm_entity_info['base table'] ?? $entity_type_id,
        'admin_permission' => 'administer civicrm entity',
        'permission_granularity' => 'entity_type',
        'handlers' => [
          'storage' => CiviEntityStorage::class,
          'access' => CivicrmEntityAccessHandler::class,
          'views_data' => CivicrmEntityViewsData::class,
          'storage_schema' => CivicrmEntityStorageSchema::class,
        ],
      ];

      if (in_array($entity_type_id, $enabled_entity_types)) {
        $entity_type_info = array_merge_recursive($entity_type_info, [
          'handlers' => [
            'list_builder' => CivicrmEntityListBuilder::class,
            'view_builder' => CiviCrmEntityViewBuilder::class,
            'route_provider' => [
              'default' => CiviCrmEntityRouteProvider::class,
            ],
            'form' => [
              'default' => CivicrmEntityForm::class,
              'add' => CivicrmEntityForm::class,
              'edit' => CivicrmEntityForm::class,
              'delete' => ContentEntityDeleteForm::class,
            ],
          ],
          // Generate route paths.
          'links' => [
            'canonical' => sprintf('/%s/{%s}', $clean_entity_type_id, $entity_type_id),
            'delete-form' => sprintf('/%s/{%s}/delete', $clean_entity_type_id, $entity_type_id),
            'edit-form' => sprintf('/%s/{%s}/edit', $clean_entity_type_id, $entity_type_id),
            'add-form' => sprintf('/%s/add', $clean_entity_type_id, $entity_type_id),
            'collection' => sprintf('/admin/structure/civicrm-entity/%s', $clean_entity_type_id),
          ],
          'field_ui_base_route' => "entity.$entity_type_id.collection",
        ]);

        if (!empty($enable_links_per_type) && in_array($entity_type_id, array_keys($enable_links_per_type))) {
          $enable_links = array_filter($enable_links_per_type[$entity_type_id]['values']);

          if (!in_array('view', $enable_links)) {
            unset($entity_type_info['links']['canonical']);
          }

          if (!in_array('delete', $enable_links)) {
            unset($entity_type_info['links']['delete-form']);
          }

          if (!in_array('edit', $enable_links)) {
            unset($entity_type_info['links']['edit-form']);
          }

          if (!in_array('add', $enable_links)) {
            unset($entity_type_info['links']['add-form']);
          }
        }

        if ($config->get('disable_links')) {
          unset(
            $entity_type_info['links']['canonical'],
            $entity_type_info['links']['delete-form'],
            $entity_type_info['links']['edit-form'],
            $entity_type_info['links']['add-form'],
          );
        }
      }
      
      // Add inline_form handler for all civicrm entity types if inline_entity_form module is enabled.
      if ($this->moduleHandler->moduleExists('inline_entity_form')) {
        $entity_type_info['handlers']['inline_form'] = '\Drupal\inline_entity_form\Form\EntityInlineForm';
      }

      // If this entity has bundle support, we define the bundle field as
      // "bundle" and will use the "bundle property" as the field to fetch field
      // options from CiviCRM with.
      //
      // @see civicrm_entity_entity_bundle_info()
      // @see \Drupal\civicrm_entity\Entity\CivicrmEntity::baseFieldDefinitions()
      if (!empty($civicrm_entity_info['bundle property'])) {
        $entity_type_info['entity_keys']['bundle'] = 'bundle';
        $entity_type_info['civicrm_bundle_property'] = $civicrm_entity_info['bundle property'];
        if (isset($entity_type_info['links']['add-form'])) {
          // For entities with bundles that are exposed, add the `bundle` key to
          // the add-form route. In CiviCrmEntityRouteProvider::getAddFormRoute
          // we default the value, so that it isn't actually required in the
          // URL.
          $entity_type_info['links']['add-form'] = sprintf('%s/{%s}', $entity_type_info['links']['add-form'], $entity_type_info['entity_keys']['bundle']);
        }
      }

      $entity_types[$entity_type_id] = new ContentEntityType($entity_type_info);
    }
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
      // We keep a bundle that is the same as the entity type ID. This allows us
      // to create fields as if this entity has no bundles.
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
   * This ensures CiviCRM Entity entity types have their field config instances
   * across all bundles. It's a copy of the Field module's logic, but clones
   * field config definitions.
   *
   * @see field_entity_bundle_field_info()
   */
  #[Hook('entity_bundle_field_info')]
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $result = [];
    if ($entity_type->get('civicrm_entity_ui_exposed') && $entity_type->hasKey('bundle')) {
      // Query by filtering on the ID as this is more efficient than filtering
      // on the entity_type property directly.
      $ids = $this->entityTypeManager
        ->getStorage('field_config')
        ->getQuery()
        ->condition('id', $entity_type->id() . '.', 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->execute();
      // Fetch all fields and key them by field name.
      $field_configs = FieldConfig::loadMultiple($ids);

      // Clone the field configs, so that we can modify them and change the
      // target bundle type without manipulating the statically cached entries
      // in the entity storage;.
      $cloned_field_configs = array_map(static function (FieldConfigInterface $field) use ($bundle) {
        $cloned = clone $field;
        $cloned->set('bundle', $bundle);
        return $cloned;
      }, $field_configs);
      foreach ($cloned_field_configs as $field_instance) {
        $result[$field_instance->getName()] = $field_instance;
      }
    }
    // Ensure all fields have a definition.
    if ($entity_type->get('civicrm_entity_ui_exposed') && $entity_type->hasKey('bundle')) {
      foreach ($base_field_definitions as $field_name => $definition) {
        if (isset($result[$field_name]) || isset($definition) || empty($bundle)) {
          continue;
        }
        $field = BaseFieldOverride::createFromBaseFieldDefinition($base_field_definitions[$field_name], $bundle);
        $result[$field_name] = $field;
      }
    }
    return $result;
  }

  /**
   * Implements hook_rebuild().
   *
   * This resets the field storage and entity type definitions for
   * civicrm_entity according to the active definitions to avoid mismatches
   * since the definitions are not necessary to be updated.
   */
  #[Hook('rebuild')]
  public function rebuild(): void {
    $supported_entities = SupportedEntities::getInfo();

    foreach (array_keys($supported_entities) as $entity_type_id) {
      // Reset field storage definitions.
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      $this->entityLastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $field_storage_definitions);

      // Reset entity type definition.
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->entityLastInstalledSchemaRepository->setLastInstalledDefinition($definition);
    }
  }

}
