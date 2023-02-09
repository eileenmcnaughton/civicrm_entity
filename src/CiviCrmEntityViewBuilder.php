<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * CiviCRM Entity View builder class.
 */
class CiviCrmEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    // Set a default theme.
    $defaults['#theme'] = 'civicrm_entity';

    return $defaults;
  }

}
