<?php

namespace Drupal\civicrm_entity\Plugin\RulesAction;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User create' action.
 *
 * @RulesAction(
 *   id = "civicrm_entity_user_create",
 *   label = @Translation("Create linked drupal user account"),
 *   category = @Translation("CiviCRM"),
 *   context = {
 *      "contact_id" = @ContextDefinition("integer",
 *        label = @Translation("CiviCRM contact ID"),
 *        description = @Translation("The CiviCRM contact ID."),
 *        required = TRUE
 *      ),
 *      "is_active" = @ContextDefinition("boolean",
 *        label = @Translation("Activate account"),
 *        description = @Translation("Set account to active."),
 *        default_value = TRUE
 *      ),
 *      "notify" = @ContextDefinition("boolean",
 *        label = @Translation("Send account notification email"),
 *        description = @Translation("Send account notification email."),
 *        default_value = TRUE
 *      ),
 *      "signin" = @ContextDefinition("boolean",
 *        label = @Translation("Instant signin"),
 *        description = @Translation("Automatically log in as the created user."),
 *        default_value = TRUE
 *      )
 *   },
 *   provides = {
 *     "civicrm_user" = @ContextDefinition("entity:user",
 *       label = @Translation("Created Drupal user")
 *     )
 *   }
 * )
 */
class UserCreate extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The CiviCRM contact storage service.
   *
   * @var \Drupal\civicrm_entity\CiviEntityStorage
   */
  protected $contactStorage;

  /**
   * The user storage service.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CiviEntityStorage $contact_storage, UserStorageInterface $user_storage, MessengerInterface $messenger, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contactStorage = $contact_storage;
    $this->userStorage = $user_storage;
    $this->messenger = $messenger;
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('civicrm_contact'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('messenger'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function doExecute($contact_id, $is_active, $notify, $signin) {
    $contact = $this->civicrmApi->getSingle('Contact', [
      'return' => ['email', 'contact_type'],
      'id' => $contact_id,
    ]);

    if (empty($contact) || empty($contact['email'])) {
      return;
    }

    $params = [
      'name' => $contact['email'],
      'mail' => $contact['email'],
      'init' => $contact['email'],
      'status' => (int) filter_var($is_active, FILTER_VALIDATE_BOOLEAN)
    ];

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->create($params);

    if ($user->validate()->count() === 0 && $user->save()) {
      $this->civicrmApi->civicrmInitialize();

      if ($contact['contact_type'] === 'Individual') {
        \CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $user->id(), $contact['email'], 'Drupal8', NULL, 'Individual', TRUE);
      }
      else {
        \CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $user->id(), $contact['email'], 'Drupal8', NULL, NULL, TRUE);
      }

      $this
        ->messenger
        ->addStatus($this->t('User with username @name has been created.', ['@name' => $user->getUsername()]));

      if ((int) filter_var($signin, FILTER_VALIDATE_BOOLEAN)) {
        user_login_finalize($user);
      }

      if ((int) filter_var($notify, FILTER_VALIDATE_BOOLEAN)) {
        _user_mail_notify('register_no_approval_required', $user);
      }
    }
  }

}
