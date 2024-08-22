<?php

namespace Drupal\civicrm_entity\Form;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalActionManager;
use Drupal\Core\Menu\LocalTaskManager;
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
   * @var \Drupal\Core\Menu\LocalActionManager
   */
  protected $localActionManager;

  /**
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $localTaskManager;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The render cache manager.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Menu\LocalActionManager $local_action_manager
   *   The local action manager.
   * @param \Drupal\Core\Menu\LocalTaskManager $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_render
   *   The render cache manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeManagerInterface $entity_type_manager, RouteBuilderInterface $route_builder, LocalActionManager $local_action_manager, LocalTaskManager $local_task_manager, MenuLinkManagerInterface $menu_link_manager, CacheBackendInterface $cache_render) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeBuilder = $route_builder;
    $this->localActionManager = $local_action_manager;
    $this->localTaskManager = $local_task_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->cacheRender = $cache_render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('plugin.manager.menu.link'),
      $container->get('cache.render')
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

    $form['enabled_entity_types'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled entity types'),
      '#collapsible' => FALSE,
    ];

    $enabled_entity_types = $config->get('enabled_entity_types') ?? [];
    $enable_links_per_type = $config->get('enable_links_per_type') ?? [];
    foreach ($civicrm_entity_types as $key => $entity_info) {
      $form['enabled_entity_types'][$key]['#type'] = 'fieldset';

      $form['enabled_entity_types'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $entity_info['civicrm entity label'],
        '#default_value' => in_array($key, $enabled_entity_types),
      ];

      $form['enabled_entity_types'][$key]['enable_links'] = [
        '#states' => [
          'visible' => [
            ':input[name="enabled_entity_types[' . $key . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
        '#type' => 'checkboxes',
        '#title' => $this->t('Enable Drupal pages'),
        // @todo Should this be a list of dynamic operations?
        '#options' => [
          'view' => $this->t('View'),
          'add' => $this->t('Add'),
          'edit' => $this->t('Edit'),
          'delete' => $this->t('Delete'),
        ],
        '#default_value' => $enable_links_per_type[$key]['values'] ?? [
          'view',
          'add',
          'edit',
          'delete',
        ],
      ];
    }

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced_settings']['disable_hooks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable pre/post hooks'),
      '#default_value' => $config->get('disable_hooks'),
      '#description' => $this->t('Not intended for normal use. Provided to temporarily disable Drupal entity hooks for CiviCRM Entity types for special cases, such as migrations. Only disable if you know you need to.'),
    ];

    $form['advanced_settings']['disable_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Drupal pages'),
      '#default_value' => $config->get('disable_links'),
      '#description' => $this->t('Globally disables Drupal versions of view page and, add, edit, and delete forms for all enabled entity types. This option overrides the "per type" Drupal pages.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $enabled_entity_types = [];
    $enable_links_per_type = [];
    foreach ($form_state->getValue('enabled_entity_types') as $entity_type => $value) {
      if ($value['enabled']) {
        $enabled_entity_types[] = $entity_type;
        $enable_links_per_type[$entity_type]['values'] = $value['enable_links'];
      }
    }

    $this->config('civicrm_entity.settings')
      ->set('filter_format', $form_state->getValue('filter_format'))
      ->set('enabled_entity_types', $enabled_entity_types)
      ->set('disable_hooks', $form_state->getValue('disable_hooks'))
      ->set('disable_links', $form_state->getValue('disable_links'))
      ->set('enable_links_per_type', $enable_links_per_type)
      ->save();

    // Need to rebuild derivative routes.
    $this->entityTypeManager->clearCachedDefinitions();
    $this->routeBuilder->rebuild();
    $this->localActionManager->clearCachedDefinitions();
    $this->localTaskManager->clearCachedDefinitions();
    $this->cacheRender->invalidateAll();
  }

}
