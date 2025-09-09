<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default argument plugin to get the current user's civicrm contact subtype.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_contact_subtype",
 *   title = @Translation("Contact subtype from logged in user")
 * )
 */
#[ViewsArgumentDefault(
  id: 'current_user_contact_subtype',
  title: new TranslatableMarkup('Contact subtype from logged in user'),
)]
class ContactSubtype extends ContactId {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['no_subtype'] = ['default' => 'none'];
    $options['multiple_subtype'] = ['default' => 'first'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['no_subtype'] = [
      '#type' => 'radios',
      '#title' => $this->t('When logged in user has no contact subtype'),
      '#default_value' => $this->options['no_subtype'],
      '#options' => [
        'none' => $this->t('Show none'),
        'all' => $this->t('Show all'),
      ],
    ];
    $form['multiple_subtype'] = [
      '#type' => 'radios',
      '#title' => $this->t('When logged in user has multiple contact subtypes'),
      '#description' => $this->t('Multiple contact subtypes requires multiple values to be set for the contextual filter (see MORE below.)'),
      '#default_value' => $this->options['multiple_subtype'],
      '#options' => [
        'first' => $this->t('Match first'),
        'any' => $this->t('Match any'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    $current_user_contact_subtype = ($this->options['no_subtype'] == 'none') ? '<none>' : 'all';

    $cid = parent::getArgument();
    if ($cid) {
      $results = $this->civicrmApi->get('contact', [
        'sequential' => 1,
        'return' => ['contact_sub_type'],
        'id' => $cid,
      ]);

      if (!empty($results) && !empty($results[0]['contact_sub_type'])) {

        // Get subtypes for argument.
        if ($this->options['multiple_subtype'] == 'first') {
          // Match first.
          $current_user_contact_subtype = reset($results[0]['contact_sub_type']);
        }
        else {
          // Match any.
          $current_user_contact_subtype = implode('+', $results[0]['contact_sub_type']);
        }
      }
    }

    return $current_user_contact_subtype;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
