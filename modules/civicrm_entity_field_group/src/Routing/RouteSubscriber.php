<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_field_group\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds dynamic bundle defaults to Field Group routes.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if (!$this->moduleHandler->moduleExists('field_ui')) {
      return;
    }

    $route_suffixes = [
      'form_display',
      'form_display.form_mode',
      'display',
      'display.view_mode',
    ];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type->get('civicrm_entity_ui_exposed') || !$entity_type->hasKey('bundle')) {
        continue;
      }

      foreach (['add', 'delete'] as $operation) {
        foreach ($route_suffixes as $suffix) {
          $route = $collection->get("field_ui.field_group_{$operation}_{$entity_type_id}.{$suffix}");
          $route?->setDefault('bundle', $entity_type_id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -250];
    return $events;
  }

}
