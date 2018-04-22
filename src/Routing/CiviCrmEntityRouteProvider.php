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
    else {
      return new RouteCollection();
    }
  }

}
