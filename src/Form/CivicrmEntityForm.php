<?php

namespace Drupal\civicrm_entity\Form;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Form object for CiviCRM Entities.
 */
class CivicrmEntityForm extends ContentEntityForm {

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Constructs a CivicrmEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, AccountInterface $current_user, RouteProvider $route_provider) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->currentUser = $current_user;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   *
   * If this CiviCRM Entity type supports bundles, we hijack the loaded entity
   * form display to be one for the root entity, not the bundle.
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $entity_type = $this->entity->getEntityType();
    if ($entity_type->hasKey('bundle')) {
      $entity_display_repository = \Drupal::service('entity_display.repository');
      assert($entity_display_repository instanceof EntityDisplayRepositoryInterface);
      $form_display = $entity_display_repository->getFormDisplay(
        $entity_type->id(),
        $entity_type->id()
      );
    }
    return parent::setFormDisplay($form_display, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form_display_info = SupportedEntities::getFormDisplayInfo($this->entity->getEntityTypeId());
    $form['#tree'] = TRUE;
    $form['#theme'] = ['civicrm_entity_entity_form'];
    $form['#attached']['library'][] = 'civicrm_entity/form';

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
      '#access' => !empty($form_display_info['groups']) && !empty($form_display_info['fields']),
    ];
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
    ];

    if (isset($form_display_info['groups'])) {
      foreach ($form_display_info['groups'] as $form_display_group_key => $form_display_group) {
        $form[$form_display_group_key] = [
          '#type' => 'details',
          '#title' => $form_display_group['title'],
          '#group' => $form_display_group['group'],
          '#weight' => 95,
          '#optional' => TRUE,
          '#open' => isset($form_display_group['open']),
        ];
      }
    }

    if (isset($form_display_info['fields'])) {
      foreach ($form_display_info['fields'] as $field_name => $field_display_info) {
        // If the field is present, change it.
        if (isset($form[$field_name])) {
          if (isset($field_display_info['group'])) {
            $form[$field_name]['#group'] = $field_display_info['group'];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $insert = $this->entity->isNew();
    $result = $this->entity->save();

    try {
      if ($this->routeProvider->getRouteByName("entity.{$this->entity->getEntityTypeId()}.canonical")) {
        $form_state->setRedirect(
          "entity.{$this->entity->getEntityTypeId()}.canonical",
          [$this->entity->getEntityTypeId() => $this->entity->id()]
        );

        $t_args = ['%title' => $this->entity->toLink()->toString()];
      }
    }
    catch (RouteNotFoundException $e) {
      $t_args = ['%title' => $this->entity->label()];
    }

    if ($insert) {
      $this->messenger()->addMessage($this->t('%title has been created.', $t_args));
    }
    else {
      $this->messenger()->addMessage($this->t('%title has been updated.', $t_args));
    }

    return $result;
  }

}
