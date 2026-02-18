<?php

namespace Drupal\as_people_ldap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\key\Entity\Key;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure LDAP settings for this site.
 */
class AsPeopleLdapSettingsForm extends ConfigFormBase {

  /**
   * The key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;


  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'as_people_ldap.settings';

  /**
   * Constructs an AsPeopleLdapSettingsForm object.
   *
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository service.
   */
  public function __construct(KeyRepositoryInterface $key_repository) {
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'as_people_ldap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */



  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Get current values from keys.
    $ldaprdn_key = $this->keyRepository->getKey('as_people_ldap_rdn');
    $ldappass_key = $this->keyRepository->getKey('as_people_ldap_password');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('LDAP Credentials'),
      '#description' => $this->t('Credentials are securely stored using the Key module and saved to the database (not exported with configuration).'),
    ];

    $form['credentials']['ldaprdn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LDAP RDN or DN'),
      '#description' => $this->t('The full LDAP RDN (Relative Distinguished Name) or DN (Distinguished Name) for binding to the LDAP server. Example: uid=drupal-ldap,ou=applications,o=Cornell University,c=US'),
      '#default_value' => $ldaprdn_key ? $ldaprdn_key->getKeyValue() : '',
      '#required' => TRUE,
    ];

    $form['credentials']['ldappass'] = [
      '#type' => 'password',
      '#title' => $this->t('LDAP Password'),
      '#description' => $this->t('The password for the LDAP bind account. Leave blank to keep current password.'),
      '#required' => FALSE,
    ];

    if ($ldappass_key) {
      $form['credentials']['password_status'] = [
        '#markup' => '<div class="messages messages--status">' . $this->t('A password is currently stored. Leave the password field blank to keep the existing password.') . '</div>',
      ];
    }

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];

    $form['options']['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t('Enable verbose debugging output for LDAP connections. Shows connection details, bind attempts, and search queries. <strong>Only works in lando/dev environments.</strong>'),
      '#default_value' => $config->get('debug_mode') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   *  $form -> An associative array containing the structure of the form.
   *  $form_state -> An associative array containing the current state of the form.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save debug mode to config (this is not sensitive).
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();

    // Save LDAP RDN to key (stored in database, not config).
    $ldaprdn_value = $form_state->getValue('ldaprdn');
    $this->saveOrUpdateKey(
      'as_people_ldap_rdn',
      'LDAP RDN',
      $ldaprdn_value,
      'LDAP bind RDN/DN for Cornell directory'
    );

    // Save LDAP password to key (only if provided).
    $ldappass_value = $form_state->getValue('ldappass');
    if (!empty($ldappass_value)) {
      $this->saveOrUpdateKey(
        'as_people_ldap_password',
        'LDAP Password',
        $ldappass_value,
        'LDAP bind password for Cornell directory'
      );
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Saves or updates a key in the key repository.
   *
   * @param string $key_id
   *   The key ID.
   * @param string $label
   *   The key label.
   * @param string $value
   *   The key value.
   * @param string $description
   *   The key description.
   */
  protected function saveOrUpdateKey($key_id, $label, $value, $description) {
    $key = $this->keyRepository->getKey($key_id);

    if ($key) {
      // Update existing key.
      $key->setKeyValue($value);
      $key->save();
    }
    else {
      // Create new key.
      $key = Key::create([
        'id' => $key_id,
        'label' => $label,
        'description' => $description,
        'key_type' => 'authentication',
        'key_type_settings' => [],
        'key_provider' => 'config',
        'key_provider_settings' => [
          'key_value' => $value,
        ],
        'key_input' => 'text_field',
        'key_input_settings' => [],
      ]);
      $key->save();
    }
  }
}
