<?php

namespace Drupal\civicrm_entity\Plugin\views\access;

use Civi\Api4\Contact;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\views\access\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides role-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "civicrm_entity_contact_checksum",
 *   title = @Translation("CiviCRM Entity: Contact Checksum"),
 *   help = @Translation("Access will be granted if the contact checksum validates against contact cid1")
 * )
 */
class ContactChecksum extends Role implements CacheableDependencyInterface {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    $instance->civicrmApi = $container->get('civicrm_entity.api');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Check if logged in and has access.
    $logged_in_access = parent::access($account);

    if ($logged_in_access) {
      return TRUE;
    }
    $request = $this->requestStack->getCurrentRequest();
    $cid1 = filter_var($request->query->get('cid1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $checksum = $request->query->get('cs');

    // Force CiviCRM to be intialized - ideally we'd use the api
    // wrapper however it's api3 and we need api4.
    // This forces a call to Civicrm initialize.
    $this->civicrmApi->getFields('Contact');

    $results = Contact::validateChecksum(FALSE)
      ->setContactId($cid1)
      ->setChecksum($checksum)
      ->execute();
    return !empty($results[0]['valid']) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_civicrm_entity_checksum_access_check', 'TRUE');
    $route->setRequirement('var_options', serialize($this->options));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Ideally we would expire based on the age of the checksum
    // however this might not work for anon users. So no caching.
    // https://www.drupal.org/docs/drupal-apis/cache-api/cache-max-age
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.query_args:checksum';
    $contexts[] = 'url.query_args:cid';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
