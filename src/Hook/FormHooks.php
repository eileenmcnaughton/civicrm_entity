<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\civicrm_entity\Form\CivicrmEntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for forms.
 */
class FormHooks {

  /**
   * Constructor for EntityHooks.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler) {
  }

  /**
   * Implements hook_form_alter().)
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof CivicrmEntityForm) {

      /**
       * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
       */
      $storage = $form_state->getStorage();
      if (!empty($storage['form_display'])) {
        $form_display = $storage['form_display'];
        $entity = $form_object->getEntity();

        if ($entity->getEntityType()->hasKey('bundle') && $this->moduleHandler->moduleExists('field_group')) {
          $context = [
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->getEntityTypeId(),
            'entity' => $entity,
            'context' => 'form',
            'display_context' => 'form',
            'mode' => $form_display->getMode(),
          ];

          field_group_attach_groups($form, $context);
        }
      }
    }
  }

}
