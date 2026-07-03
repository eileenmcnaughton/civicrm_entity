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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
            'add-form' => sprintf('/%s/add', $clean_entity_type_id),
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

      // Add inline_form handler when inline_entity_form module is enabled.
      if ($this->moduleHandler->moduleExists('inline_entity_form')) {
        $entity_type_info['handlers']['inline_form'] = '\Drupal\inline_entity_form\Form\EntityInlineForm';
      }

      $entity_types[$entity_type_id] = new ContentEntityType($entity_type_info);
    }
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
