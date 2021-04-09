<?php

namespace Drupal\civicrm_entity\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *    The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
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
        "layout_builder.defaults.$entity_type_id.view" => [
          'bundle' => $entity_type_id,
        ],
      ];
      foreach ($field_ui_routes as $route_name => $defaults) {
        if ($route = $collection->get($route_name)) {
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
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Field UI's route subscriber runs at -100.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
