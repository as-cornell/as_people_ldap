<?php
namespace Drupal\as_people_ldap\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\MapArray;


class AsPeopleLdapSettingsForm extends ConfigFormBase {


  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'as_people_ldap.settings';

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

    $form['ldaprdn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LDAP RDN or DN'),
      '#description' => $this->t('The full LDAP RDN (Relative Distinguished Name) or DN (Distinguished Name) for binding to the LDAP server.'),
      '#default_value' => $config->get('ldaprdn'),
      '#required' => TRUE,
    ];

    $form['ldappass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LDAP Password'),
      '#description' => $this->t('The password for the LDAP bind account.'),
      '#default_value' => $config->get('ldappass'),
      '#required' => TRUE,
    ];

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t('Enable verbose debugging output for LDAP connections. Shows connection details, bind attempts, and search queries. Only use in development environments.'),
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
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('ldaprdn', $form_state->getValue('ldaprdn'))
      ->set('ldappass', $form_state->getValue('ldappass'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
