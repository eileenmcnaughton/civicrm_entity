<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Reverse CiviCRM entity reference locations for phone.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_reverse_location_phone")
 */
class EntityReverseLocationPhone extends EntityReverseLocation {

  /**
   * An array of CiviCRM phone types.
   *
   * @var array
   */
  protected $phoneTypes = [];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->phoneTypes = \CRM_Core_BAO_Phone::buildOptions('phone_type_id');

    if (!empty($this->options['phone_type'])) {
      $this->definition['extra'][] = [
        'field' => 'phone_type_id',
        'value' => $this->options['phone_type'],
        'numeric' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['phone_type'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['phone_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Phone type'),
      '#options' => [0 => $this->t('Any')] + $this->phoneTypes,
      '#default_value' => isset($this->options['phone_type']) ? (int) $this->options['phone_type'] : 0,
      '#weight' => -2,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}
