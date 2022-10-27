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
 *   context_definitions = {
 *      "contact_id" = @ContextDefinition("integer",
 *        label = @Translation("CiviCRM contact ID"),
 *        description = @Translation("The CiviCRM contact ID."),
 *        required = TRUE
 *      ),
 *      "is_active" = @ContextDefinition("boolean",
 *        label = @Translation("Activate account"),
 *        description = @Translation("Set to TRUE to activate account. Leave empty to NOT activate the account. Defaults to TRUE."),
 *        assignment_restriction = "input",
 *        default_value = "TRUE",
 *        required = FALSE
 *      ),
 *      "notify" = @ContextDefinition("boolean",
 *        label = @Translation("Send account notification email"),
 *        description = @Translation("Set to TRUE to send a notification email. Leave empty to not send an account notification email."),
 *        assignment_restriction = "input",
 *        default_value = FALSE,
 *        required = FALSE
 *      ),
 *      "signin" = @ContextDefinition("boolean",
 *        label = @Translation("Instant signin"),
 *        description = @Translation("Set to TRUE to automatically log in the user. Leave empty to not automatically log in the user."),
 *        assignment_restriction = "input",
 *        default_value = FALSE,
 *        required = FALSE
 *      ),
 *      "format" = @ContextDefinition("string",
 *        label = @Translation("Format"),
 *        description = @Translation("Format of the username.")
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
  public function doExecute($contact_id, $is_active, $notify, $signin, $format) {
    $contact = $this->civicrmApi->getSingle('Contact', [
      'return' => ['email', 'contact_type'],
      'id' => $contact_id,
    ]);

    if (empty($contact) || empty($contact['email'])) {
      return;
    }

    $is_active = filter_var($is_active, FILTER_VALIDATE_BOOLEAN);
    $signin = filter_var($signin, FILTER_VALIDATE_BOOLEAN);
    $notify = filter_var($notify, FILTER_VALIDATE_BOOLEAN);

    $params = [
      'name' => $format,
      'mail' => $contact['email'],
      'init' => $contact['email'],
      'status' => (bool) $is_active,
    ];

    $this->civicrmApi->civicrmInitialize();
    $config = \CRM_Core_Config::singleton();

    if ($this->checkUserNameExists($params, $config->userSystem)) {
      $counter = 0;
      do {
        // Try to add an extension to username.
        $params['name'] = $format . '_' . $counter++;
      } while ($this->checkUserNameExists($params, $config->userSystem)
              // Exit loop if to many errors
              // Invalid charater in username for example.
              && $counter < 10);
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->create($params);

    $violations = $user->validate()->getByFields(array_keys($params));

    if ($violations->count() > 0) {
      $messages = 'Unable to create user for %email due to the following error(s):<ul>';

      /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
      foreach ($violations as $violation) {
        $messages .= '<li>' . $violation->getMessage() . '</li>';
      }

      $messages .= '</ul>';

      \Drupal::logger('civicrm_entity')->error($messages, ['%email' => $params['mail']]);
    }

    if ($violations->count() === 0 && $user->save()) {
      $this->civicrmApi->civicrmInitialize();

      if ($contact['contact_type'] === 'Individual') {
        \CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $user->id(), $contact['email'], 'Drupal8', NULL, 'Individual', TRUE);
      }
      else {
        \CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $user->id(), $contact['email'], 'Drupal8', NULL, NULL, TRUE);
      }

      $this
        ->messenger
        ->addStatus($this->t('User with username @name has been created.', ['@name' => $user->getDisplayName()]));

      $this->setProvidedValue('civicrm_user', $user);

      if ((bool) $signin) {
        user_login_finalize($user);
      }

      if ((bool) $notify) {
        _user_mail_notify('register_no_approval_required', $user);
      }
    }
  }

  /**
   * Check if username exists.
   *
   * @param array $params
   *   The parameters.
   * @param \CRM_Utils_System_Base $userSystem
   *   The user system.
   *
   * @return bool
   *   TRUE if username exists; otherwise FALSE.
   */
  protected function checkUserNameExists(array $params, \CRM_Utils_System_Base $userSystem) {
    $errors = [];
    $userSystem->checkUserNameEmailExists($params, $errors);

    return isset($errors['cms_name']);
  }

}
