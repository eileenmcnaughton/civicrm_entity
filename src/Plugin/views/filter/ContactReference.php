<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An "Contact reference" handler to include CiviCRM API.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("civicrm_entity_contact_reference")
 */
class ContactReference extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

  /**
   * The contact storage.
   *
   * @var \Drupal\civicrm_entity\CiviEntityStorage
   */
  protected $contactStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm_entity.api'),
      $container->get('database')
    );

    $instance->contactStorage = $container->get('entity_type.manager')->getStorage('civicrm_contact');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $contacts = $this->value ? $this->contactStorage->loadMultiple($this->value) : [];
    $default_value = EntityAutocomplete::getEntityLabels($contacts);
    $form['value'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Contacts'),
      '#description' => $this->t('Enter a comma separated list of CiviCRM contacts.'),
      '#target_type' => 'civicrm_contact',
      '#tags' => TRUE,
      '#default_value' => $default_value,
      '#process_default_value' => $this->isExposed(),
    ];

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $ids = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $ids[] = $value['target_id'];
      }
      sort($ids);
    }
    $form_state->setValue(['options', 'value'], $ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $rc = parent::acceptExposedInput($input);

    if ($rc) {
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $this->valueOptions = [];

    if ($this->value) {
      $result = $this->contactStorage->loadByProperties(['id' => $this->value]);
      foreach ($result as $contact) {
        if ($contact->id()) {
          $this->valueOptions[$contact->id()] = $contact->label();
        }
      }
    }

    return parent::adminSummary();
  }

}
