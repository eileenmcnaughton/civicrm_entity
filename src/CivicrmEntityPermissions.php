<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CivicrmEntityPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }


  public function buildPermissions() {
    $permissions = [];
    $civicrm_entity_data = SupportedEntities::getInfo();
    $civicrm_entities = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $type->getProvider() == 'civicrm_entity' && $type->get('civicrm_entity_ui_exposed');
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $civicrm_entity */
    foreach ($civicrm_entities as $civicrm_entity) {
      $entity_type_id = $civicrm_entity->id();
      $plural_label = $civicrm_entity->getPluralLabel();
      $civicrm_entity_permissions = $civicrm_entity_data[$entity_type_id]['permissions'];

      $permissions["administer {$entity_type_id}"] = [
        'title' => $this->t('Administer @type', ['@type' => $plural_label]),
        'restrict access' => TRUE,
      ];
      if ($civicrm_entity->hasLinkTemplate('collection')) {
        $permissions["access {$entity_type_id} overview"] = [
          'title' => $this->t('Access the @type overview page', ['@type' => $plural_label]),
        ];
      }

      if (!empty($civicrm_entity_permissions['view'])) {
        $permissions["view {$entity_type_id}"] = [
          'title' => $this->t('View @type', [
            '@type' => $plural_label,
          ]),
        ];
      }
      if (!empty($civicrm_entity_permissions['update'])) {
        $permissions["update {$entity_type_id}"] = [
          'title' => $this->t('Update @type', [
            '@type' => $plural_label,
          ]),
        ];
      }
      if (!empty($civicrm_entity_permissions['create'])) {
        $permissions["create {$entity_type_id}"] = [
          'title' => $this->t('Create @type', [
            '@type' => $plural_label,
          ]),
        ];
      }
      if (!empty($civicrm_entity_permissions['delete'])) {
        $permissions["delete {$entity_type_id}"] = [
          'title' => $this->t('Delete @type', [
            '@type' => $plural_label,
          ]),
        ];
      }
    }

    return $permissions;
  }
}
