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
    if ($container->hasDefinition('module_installer')) {
      $definition = $container->getDefinition('module_installer');
      $definition->setClass('Drupal\civicrm_entity\ModuleInstaller');
    }
  }

}
