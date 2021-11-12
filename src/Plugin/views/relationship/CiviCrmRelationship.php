<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\civicrm_entity\Plugin\views\relationship\EntityReverse as CivicrmEntityReverse;
use Drupal\Core\Form\FormStateInterface;

/**
 * Relationship for referencing civicrm_contact and civicrm_relationship.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_civicrm_relationship")
 */
class CiviCrmRelationship extends CivicrmEntityReverse {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['relationship_type'] = ['default' => []];
    $options['relationship_state'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['relationship_type'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Choose a specific relationship type(s)'),
      '#default_value' => $this->options['relationship_type'] ?? [],
      '#options' => $this->getRelationshipTypes(),
      '#description' => $this->t('Choose to limit this relationship to one or more specific types of CiviCRM relationship.'),
    ];
    $form['relationship_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit results only to active relationships?'),
      '#description' => $this->t('Exclude relationships that are inactive.'),
      '#default_value' => !empty($this->options['relationship_state']),
    ];
  }

  /**
   * Get the list of relationship types.
   */
  private function getRelationshipTypes() {
    $relTypes = \Drupal::service('civicrm_entity.api')->get('RelationshipType', [
      'return' => ["id", "label_a_b", "label_b_a"],
      'options' => ['limit' => 0],
    ]);
    $options = [];
    foreach ($relTypes as $info) {
      $options[$info['id']] = "{$info['label_a_b']} | {$info['label_b_a']}";
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->options['relationship_type'])) {
      $this->definition['extra'][] = [
        'field' => 'relationship_type_id',
        'value' => $this->options['relationship_type'],
      ];
    }

    if (!empty($this->options['relationship_state'])) {
      $this->definition['extra'][] = [
        'field' => 'is_active',
        'value' => 1,
      ];
    }
    parent::query();
  }

}
