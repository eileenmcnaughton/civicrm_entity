<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Display file link base on CiviCRM.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_custom_file")
 */
class CustomFile extends FieldPluginBase {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
    $this->additional_fields['entity_id'] = 'entity_id';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($value) {
      $path = 'civicrm/file';
      $file_type = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File', $value, 'mime_type');

      // @todo Maybe grab these from the API?
      $file_types = [
        'image/jpeg',
        'image/pjpeg',
        'image/gif',
        'image/x-png',
        'image/png',
      ];

      if ($file_type && in_array($file_type, $file_types)) {
        $path = sprintf('%s/imagefile', $path);
      }

      $entity_id = $this->getValue($values, 'entity_id');
      $file_hash = \CRM_Core_BAO_File::generateFileHash($entity_id, $value);

      $query = ['id' => $value, 'eid' => $entity_id, 'fcs' => $file_hash, 'reset' => 1];
      return \CRM_Utils_System::url($path, UrlHelper::buildQuery($query), TRUE, FALSE, FALSE, TRUE);
    }

    return NULL;
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

}
