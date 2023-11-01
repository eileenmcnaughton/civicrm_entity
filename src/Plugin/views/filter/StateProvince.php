<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\options\Plugin\views\filter\ListField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for proximity.
 *
 * @ViewsFilter("civicrm_entity_civicrm_address_state_province")
 */
class StateProvince extends ListField {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;

    $this->civicrmApi->civicrmInitialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $exposed = $form_state->get('exposed');
    $view = $this->view;
    $handler = $view->getHandler($this->view->current_display, 'filter', 'country_id');

    if ($exposed && !empty($handler) && $handler['table'] == 'civicrm_address' && $handler['field'] == 'country_id') {
      $user_input = $form_state->getUserInput();

      $selected = [];
      if (isset($user_input[$this->options['expose']['identifier']])) {
        $selected = is_array($user_input[$this->options['expose']['identifier']]) ? $user_input[$this->options['expose']['identifier']] : [$user_input[$this->options['expose']['identifier']]];
      }

      if ($handler['exposed']) {
        $countries = array_keys(\CRM_Core_PseudoConstant::country());
        $country_states = $this->getStates($countries);

        // Convert to HTML options.
        $js_country_states = [];
        foreach ($country_states as $country_id => $states) {
          if (empty($js_country_states[$country_id])) {
            $js_country_states[$country_id] = '';
          }

          foreach ($states as $k => $v) {
            $js_country_states[$country_id] .= '<option value="' . $k . '"' . (in_array($k, $selected) ? 'selected="selected"' : '') . '>' . $v . '</option>';
          }
        }
        $form['#attached']['drupalSettings']['civicrm_entity']['states_identifier'] = Html::cleanCssIdentifier($this->options['expose']['identifier']);
        $form['#attached']['drupalSettings']['civicrm_entity']['country_identifier'] = Html::cleanCssIdentifier($handler['expose']['identifier']);
        $form['#attached']['drupalSettings']['civicrm_entity']['states'] = $js_country_states;
        $form['#attached']['library'][] = 'civicrm_entity/states';
      }
      else {
        $selected_countries = is_array($handler['value']) ? $handler['value'] : [$handler['value']];
        $country_states = $this->getStates($selected_countries);
        $this->valueOptions = [];
        foreach ($country_states as $states) {
          $this->valueOptions += $states;
        }
      }
    }

    parent::valueForm($form, $form_state);
  }

  /**
   * Gets the corresponding states.
   *
   * @param array $countries
   *   The countries.
   *
   * @return array
   *   The states keyed by country_id.
   */
  protected function getStates(array $countries): array {
    $states = [];

    $countries = implode(', ', $countries);
    $query = "SELECT id, name, country_id FROM civicrm_state_province WHERE country_id IN ($countries) ORDER BY name ASC";
    $result = \CRM_Core_DAO::executeQuery($query);

    while ($result->fetch()) {
      $states[$result->country_id][$result->id] = $result->name;
    }

    return $states;
  }

}
