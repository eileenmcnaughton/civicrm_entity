<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Entity\ContentUninstallValidator as EntityContentUninstallValidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Validates module uninstall readiness for CiviCRM entities.
 */
class ContentUninstallValidator extends EntityContentUninstallValidator {

  /**
   * The content uninstall validator service.
   *
   * @var \Drupal\Core\Entity\ContentUninstallValidator
   */
  protected $contentUninstallValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleUninstallValidatorInterface $content_uninstall_validator, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    parent::__construct($entity_type_manager, $string_translation);
    $this->contentUninstallValidator = $content_uninstall_validator;
  }

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
