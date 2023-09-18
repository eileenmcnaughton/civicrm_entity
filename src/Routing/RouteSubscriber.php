<?php

namespace Drupal\civicrm_entity\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters generated routes for CiviCRM Entity entity definitions.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Only run if Field UI is installed.
    if (!$this->moduleHandler->moduleExists('field_ui')) {
      return;
    }

    $has_layout_builder = $this->moduleHandler->moduleExists('layout_builder');
    $has_field_group = $this->moduleHandler->moduleExists('field_group');
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type->get('civicrm_entity_ui_exposed')) {
        continue;
      }
      if (!$entity_type->hasKey('bundle')) {
        continue;
      }

      $field_ui_routes = [
        "entity.{$entity_type_id}.field_ui_fields" => [
          'bundle' => $entity_type_id,
        ],
        "entity.field_config.{$entity_type_id}_field_edit_form" => [
          'bundle' => $entity_type_id,
        ],
        "entity.field_config.{$entity_type_id}_storage_edit_form" => [
          'bundle' => $entity_type_id,
        ],
        "entity.field_config.{$entity_type_id}_field_delete_form" => [
          'bundle' => $entity_type_id,
        ],
        "field_ui.field_storage_config_add_$entity_type_id" => [
          'bundle' => $entity_type_id,
        ],
        "field_ui.field_storage_config_reuse_{$entity_type_id}" => [
          'bundle' => $entity_type_id,
        ],
        "entity.entity_form_display.{$entity_type_id}.default" => [
          'bundle' => $entity_type_id,
        ],
        "entity.entity_form_display.{$entity_type_id}.form_mode" => [
          'bundle' => $entity_type_id,
        ],
        "entity.entity_view_display.{$entity_type_id}.default" => [
          'bundle' => $entity_type_id,
        ],
        "entity.entity_view_display.{$entity_type_id}.view_mode" => [
          'bundle' => $entity_type_id,
        ],
      ];

      if ($has_layout_builder) {
        // @todo we should iterate over the section storage definitions.
        //   that means we'd need to conditionally inject the manage service.
        // @see \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage::buildRoutes
        $field_ui_routes["layout_builder.defaults.$entity_type_id.view"] = [
          'bundle' => $entity_type_id,
        ];
        // @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::buildRoutes
        $field_ui_routes["layout_builder.overrides.$entity_type_id.view"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["layout_builder.defaults.$entity_type_id.discard_changes"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["layout_builder.defaults.$entity_type_id.disable"] = [
          'bundle' => $entity_type_id,
        ];
      }

      if ($has_field_group) {
        $field_ui_routes["field_ui.field_group_add_$entity_type_id.form_display"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_add_$entity_type_id.form_display.form_mode"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_add_$entity_type_id.display"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_add_$entity_type_id.display.view_mode"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_delete_$entity_type_id.form_display"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_delete_$entity_type_id.form_display.form_mode"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_delete_$entity_type_id.display"] = [
          'bundle' => $entity_type_id,
        ];

        $field_ui_routes["field_ui.field_group_delete_$entity_type_id.display.view_mode"] = [
          'bundle' => $entity_type_id,
        ];
      }

      foreach ($field_ui_routes as $route_name => $defaults) {
        $route = $collection->get($route_name);

        if ($route) {
          foreach ($defaults as $name => $default) {
            $route->setDefault($name, $default);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events = parent::getSubscribedEvents();
    // Field UI's route subscriber runs at -100.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -250];
    return $events;
  }

}
