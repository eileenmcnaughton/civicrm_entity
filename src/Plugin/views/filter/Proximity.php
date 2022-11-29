<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Database\Query\Condition;

/**
 * Filter handler for proximity.
 *
 * @ViewsFilter("civicrm_entity_civicrm_address_proximity")
 */
class Proximity extends FilterPluginBase {

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

    $this->alwaysMultiple = TRUE;
    $this->no_operator = TRUE;
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
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
    \CRM_Contact_BAO_ProximityQuery::initialize();
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['value'] = [
      'contains' => [
        'value' => ['default' => ''],
        'city' => ['default' => ''],
        'state_province_id' => ['default' => ''],
        // 'country' => ['default' => ''],
        'distance' => ['default' => ''],
        'distance_unit' => ['default' => ''],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    return $this->t('within @postal_code', ['@postal_code' => $this->value['value']]);
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value']['#tree'] = TRUE;
    $form['value']['#type'] = 'fieldset';
    $form['value']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#size' => 30,
      '#default_value' => $this->value['city'],
    ];

    $config = \CRM_Core_Config::singleton();
    $values = $this->civicrmApi->get('StateProvince', [
      'sequential' => 1,
      'country_id' => $config->defaultContactCountry,
      'options' => ['limit' => 0],
    ]);

    $form['value']['state_province_id'] = [
      '#type' => 'select',
      '#options' => ['' => $this->t('- Any -')] + array_combine(
        array_column($values, 'id'),
        array_column($values, 'name')
      ),
      '#title' => $this->t('State/Province'),
      '#default_value' => $this->value['state_province_id'],
    ];

    $form['value']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code'),
      '#size' => 30,
      '#default_value' => $this->value['value'],
    ];

    $form['value']['distance'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance'),
      '#default_value' => $this->value['distance'],
    ];

    $form['value']['distance_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance unit'),
      '#default_value' => $this->value['distance_unit'],
      '#options' => [
        'miles' => $this->t('Miles'),
        'kilometers' => $this->t('Kilometers'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Make sure that postal code and distance are set before altering the
    // query.
    if ((!empty($this->value['value']) || !empty($this->value['city']) || !empty($this->value['state_province_id'])) && !empty($this->value['distance'])) {
      $distance = $this->getCalculatedDistance($this->value['distance'], $this->value['distance_unit']);

      $config = \CRM_Core_Config::singleton();
      $countries = $this
        ->civicrmApi
        ->get('Country', [
          'sequential' => 1,
          'id' => $config->defaultContactCountry,
          'return' => ['name'],
        ]);

      $proximity_address = [
        'postal_code' => $this->value['value'],
        'state_province_id' => $this->value['state_province_id'],
        'city' => $this->value['city'],
        'country' => !empty($countries) ? $countries[0]['name'] : '',
        'country_id' => $config->defaultContactCountry,
        'distance_unit' => $this->value['distance_unit'],
      ];

      $geocoded_address = $this->getGeocodedAddress($proximity_address);

      [$min_longitude, $max_longitude] = \CRM_Contact_BAO_ProximityQuery::earthLongitudeRange($geocoded_address['longitude'], $geocoded_address['latitude'], $distance);
      [$min_latitude, $max_latitude] = \CRM_Contact_BAO_ProximityQuery::earthLatitudeRange($geocoded_address['longitude'], $geocoded_address['latitude'], $distance);

      $this->ensureMyTable();

      $condition = new Condition('AND');

      if (!is_nan($min_latitude)) {
        $condition->condition("{$this->tableAlias}.geo_code_1", $min_latitude, '>=');
      }

      if (!is_nan($max_latitude)) {
        $condition->condition("{$this->tableAlias}.geo_code_1", $max_latitude, '<=');
      }

      if (!is_nan($min_longitude)) {
        $condition->condition("{$this->tableAlias}.geo_code_2", $min_longitude, '>=');
      }

      if (!is_nan($max_longitude)) {
        $condition->condition("{$this->tableAlias}.geo_code_2", $max_longitude, '<=');
      }

      if ($condition->count() > 0) {
        $this->query->addWhere($this->options['group'], $condition);
      }

      $expression = "
        ACOS(
          COS(RADIANS({$this->tableAlias}.geo_code_1)) *
          COS(RADIANS({$geocoded_address['latitude']})) *
          COS(RADIANS({$this->tableAlias}.geo_code_2) - RADIANS({$geocoded_address['longitude']})) +
          SIN(RADIANS({$this->tableAlias}.geo_code_1)) *
          SIN(RADIANS({$geocoded_address['latitude']}))
        ) * 6378137
      ";

      $this->query->addWhereExpression($this->options['group'], "$expression <= $distance");
    }
  }

  /**
   * Get the distance.
   *
   * @param int $distance
   *   The distance.
   * @param string $distance_unit
   *   The distance unit whether i.e. miles or kilometers.
   *
   * @return float
   *   The calculated distance depending on the distance unit.
   *
   * @see \CRM_Contact_BAO_ProximityQuery::process()
   */
  protected function getCalculatedDistance($distance, $distance_unit) {
    switch ($distance_unit) {
      case 'miles':
        $distance *= 1609.344;
        break;

      case 'kilometers':
      default:
        $distance *= 1000.00;
        break;
    }

    return $distance;
  }

  /**
   * Get the geocoded data.
   *
   * @param array $address
   *   Address based on the format of CRM_Core_BAO_Address::addGeocoderData().
   *
   * @return array
   *   An array of geocoded data based on the address.
   *
   * @see CRM_Core_BAO_Address::addGeocoderData()
   */
  protected function getGeocodedAddress(array $address) {
    $address = array_filter($address);

    if (!\CRM_Core_BAO_Address::addGeocoderData($address)) {
      throw new \Exception('Unable to properly geocode address.');
    }

    return [
      'latitude' => $address['geo_code_1'],
      'longitude' => $address['geo_code_2'],
    ];
  }

}
