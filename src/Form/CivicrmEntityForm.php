<?php

namespace Drupal\civicrm_entity\Form;

use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CivicrmEntityForm extends ContentEntityForm {

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a NodeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, AccountInterface $current_user) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
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
      '#access' => !empty($form_display_info['groups']) && !empty($form_display_info['fields'])
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
    $this->entity->save();

    $t_args = ['%title' => $this->entity->toLink()->toString()];
    if ($insert) {
      drupal_set_message($this->t('%title has been created.', $t_args));
    }
    else {
      drupal_set_message($this->t('%title has been updated.', $t_args));
    }
    $form_state->setRedirect(
      "entity.{$this->entity->getEntityTypeId()}.canonical",
      [$this->entity->getEntityTypeId() => $this->entity->id()]
    );
  }

}
