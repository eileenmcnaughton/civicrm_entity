<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service provider for the CiviCRM Entity module.
 */
final class CivicrmEntityServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('content_uninstall_validator')) {
      $definition = $container->getDefinition('content_uninstall_validator');
      $definition->setClass('Drupal\civicrm_entity\Entity\ContentUninstallValidator');
    }
  }

}
