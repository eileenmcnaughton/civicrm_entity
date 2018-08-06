<?php

namespace Drupal\civicrm_entity\Form;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for CiviCRM entity settings.
 */
class CivicrmEntitySettings extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, RouteBuilderInterface $route_builder, LocalActionManagerInterface $local_action_manager, LocalTaskManagerInterface $local_task_manager, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeBuilder = $route_builder;
    $this->localActionManager = $local_action_manager;
    $this->localTaskManager = $local_task_manager;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['civicrm_entity.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_entity_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('civicrm_entity.settings');

    $formats = filter_formats();
    $form['filter_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text format'),
      '#options' => array_map(function (FilterFormatInterface $filter) {
        return $filter->label();
      }, $formats),
      '#default_value' => $config->get('filter_format') ?: filter_fallback_format(),
      '#access' => count($formats) > 1 && $this->currentUser()->hasPermission('administer filters'),
      '#attributes' => ['class' => ['filter-list']],
    ];

    $civicrm_entity_types = SupportedEntities::getInfo();
    // @todo Use tableselect so we can display entity descriptions.
    $options = array_map(function (array $entity_info) {
      return $entity_info['civicrm entity label'];
    }, $civicrm_entity_types);
    asort($options);

    $form['enabled_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled entity types'),
      '#options' => $options,
      '#default_value' => $config->get('enabled_entity_types'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $enabled_entity_type = array_filter($form_state->getValue('enabled_entity_types'));
    $this->config('civicrm_entity.settings')
      ->set('filter_format', $form_state->getValue('filter_format'))
      ->set('enabled_entity_types', $enabled_entity_type)
      ->save();

    // Need to rebuild derivative routes.
    $this->entityTypeManager->clearCachedDefinitions();
    $this->routeBuilder->rebuild();
    $this->localActionManager->clearCachedDefinitions();
    $this->localTaskManager->clearCachedDefinitions();
  }

}
