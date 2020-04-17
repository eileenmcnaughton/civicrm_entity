<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Display CiviCRM content using Drupal text formats.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_markup")
 */
class Markup extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => 'plain_text'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text format'),
      '#description' => $this->t('Select a text format for this value.'),
      '#default_value' => $this->options['format'],
    ];

    foreach (filter_formats() as $format) {
      $form['format']['#options'][$format->id()] = $format->label();
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return check_markup($this->getValue($values), $this->options['format']);
  }

}
