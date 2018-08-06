<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CivicrmEntityViewsData extends EntityViewsData {

  use StringTranslationTrait;

  /**
   * Constructs an EntityViewsData object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to provide views integration for.
   * @param \Drupal\civicrm_entity\CiviEntityStorage $storage_controller
   *   The storage handler used for this entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(EntityTypeInterface $entity_type, CiviEntityStorage $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager) {
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->storage = $storage_controller;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('typed_data_manager')
    );
  }


  public function getViewsData() {
    $data = [];
    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();
    $base_field = $this->entityType->getKey('id');

    // Setup base information of the views data.
    $data[$base_table]['table']['group'] = sprintf('%s (CiviCRM Entity)', $this->entityType->getLabel());
    $data[$base_table]['table']['provider'] = $this->entityType->getProvider();

    $views_base_table = $base_table;
    $data[$views_base_table]['table']['base'] = [
      'field' => $base_field,
      'title' => $this->entityType->getLabel(),
      'cache_contexts' => $this->entityType->getListCacheContexts(),
    ];
    $data[$base_table]['table']['entity revision'] = FALSE;
    if ($label_key = $this->entityType->getKey('label')) {
      $data[$views_base_table]['table']['base']['defaults'] = [
        'field' => $label_key,
      ];
    }

    // Entity types must implement a list_builder in order to use Views'
    // entity operations field.
    if ($this->entityType->hasListBuilderClass()) {
      $data[$base_table]['operations'] = [
        'field' => [
          'title' => $this->t('Operations links'),
          'help' => $this->t('Provides links to perform entity operations.'),
          'id' => 'entity_operations',
        ],
      ];
    }

    if ($this->entityType->hasViewBuilderClass()) {
      $data[$base_table]['rendered_entity'] = [
        'field' => [
          'title' => $this->t('Rendered entity'),
          'help' => $this->t('Renders an entity in a view mode.'),
          'id' => 'rendered_entity',
        ],
      ];
    }

    $this->addEntityLinks($data[$base_table]);

    // Load all typed data definitions of all fields. This should cover each of
    // the entity base, revision, data tables.
    $field_definitions = $this->entityManager->getBaseFieldDefinitions($this->entityType->id());

    $table_mapping = $this->storage->getTableMapping();

    if ($table_mapping) {

      foreach ($table_mapping->getTableNames() as $table) {
        foreach ($table_mapping->getFieldNames($table) as $field_name) {
          $stop = null;
        }
      }

      foreach ($field_definitions as $field_definition) {
        if ($table_mapping->allowsSharedTableStorage($field_definition->getFieldStorageDefinition())) {
          $this->mapFieldDefinition($views_base_table, $field_definition->getName(), $field_definition, $table_mapping, $data[$views_base_table]);
        }
        else if ($table_mapping->requiresDedicatedTableStorage($field_definition->getFieldStorageDefinition())) {
          $table = $table_mapping->getDedicatedDataTableName($field_definition->getFieldStorageDefinition());

          $data[$table]['table']['group'] = $this->entityType->getLabel();
          $data[$table]['table']['provider'] = $this->entityType->getProvider();
          $data[$table]['table']['join'][$views_base_table] = [
            'left_field' => $base_field,
            'field' => 'entity_id',
            'extra' => [
              ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
            ],
          ];
        }
      }
    }

    // Add the entity type key to each table generated.
    $entity_type_id = $this->entityType->id();
    array_walk($data, function (&$table_data) use ($entity_type_id) {
      $table_data['table']['entity type'] = $entity_type_id;
    });

    return $data;
  }

  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    // CiviCRM Entity tables are `civicrm_*`
    return $entity_type->id();
  }

  /**
   * Provides Views integration for any datetime-based fields.
   *
   * This does not provide arguments, as that required an alter against the
   * entire Views data array, which is not possible here.
   *
   * @param string $table
   *   The table the language field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   *
   * @see datetime_type_field_views_data_helper()
   */
  protected function processViewsDataForDatetime($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    // Set the 'datetime' filter type.
    $views_field['filter']['id'] = 'datetime';
    $views_field['filter']['field_name'] = $field_definition->getName();

    // Set the 'datetime' argument type.
    $views_field['argument']['id'] = 'datetime';
    $views_field['argument']['field_name'] = $field_definition->getName();

    // Set the 'datetime' sort handler.
    $views_field['sort']['id'] = 'datetime';
    $views_field['sort']['field_name'] = $field_definition->getName();
  }

  /**
   * Provides Views integration for list_string fields.
   *
   * This does not provide arguments, as that required an alter against the
   * entire Views data array, which is not possible here.
   *
   * @param string $table
   *   The table the language field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   *
   * @see options_field_views_data()
   */
  protected function processViewsDataForListString($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    $views_field['filter']['id'] = 'list_field';
    $views_field['filter']['field_name'] = $field_definition->getName();

    // Set the 'datetime' argument type.
    if ($field_definition->getName() == 'list_string') {
      $views_field['argument']['id'] = 'string_list_field';
    }
    else {
      $views_field['argument']['id'] = 'number_list_field';
    }
    $views_field['argument']['field_name'] = $field_definition->getName();
  }

}
