<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Validates whether the argument matches a contact type.
 */
class CivicrmContact extends Entity {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['contact_type'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $contact_types = ['Individual', 'Organization', 'Household'];
    $form['contact_type'] = [
      '#title' => $this->t('Contact type'),
      '#default_value' => $this->options['contact_type'],
      '#type' => 'checkboxes',
      '#options' => array_combine($contact_types, $contact_types),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = []) {
    $options['contact_type'] = array_filter($options['contact_type']);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntity(EntityInterface $entity) {
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $valid = TRUE;

    if (!empty($this->options['contact_type']) && !in_array($entity->get('contact_type')->value, $this->options['contact_type'])) {
      $valid = FALSE;
    }

    return $valid && parent::validateEntity($entity);
  }

}
