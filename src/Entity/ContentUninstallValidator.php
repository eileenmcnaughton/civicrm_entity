<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Entity\ContentUninstallValidator as EntityContentUninstallValidator;

/**
 * Content uninstall validator class.
 */
class ContentUninstallValidator extends EntityContentUninstallValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    if ($module === 'civicrm_entity') {
      return [];
    }

    return parent::validate($module);
  }

}
