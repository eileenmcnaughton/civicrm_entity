<?php

namespace Drupal\civicrm_entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routing for CiviCRM entities.
 */
class CiviCrmEntityRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    if ($entity_type->get('civicrm_entity_ui_exposed')) {
      return parent::getRoutes($entity_type);
    }

    return new RouteCollection();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $has_bundles = $entity_type->hasKey('bundle');
    $entity_add_form_route = parent::getAddFormRoute($entity_type);
    if ($has_bundles && $entity_add_form_route) {
      // This ensures the form receives a default bundle from the
      // CivicrmEntity::preCreate method, avoiding the need for the `add_page`
      // route for selecting a bundle.
      assert($entity_add_form_route !== NULL);
      $entity_add_form_route->setDefault('bundle', $entity_type->id());
    }
    return $entity_add_form_route;
  }

}
