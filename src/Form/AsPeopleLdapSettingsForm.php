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


    // switch to db based config
    $config = $this->config(static::SETTINGS);
    $form['ldaprdn'] = array(
      '#type' => 'textfield',
      '#description' => t('ldap rdn or dn'),
      '#title' => t('ldap rdn or dn'),
      '#default_value' => $config->get('ldaprdn'),
    );
    $form['ldappass'] = array(
      '#type' => 'textfield',
      '#description' => t('ldap password'),
      '#title' => t('ldap password'),
      '#default_value' => $config->get('ldappass'),
    );
    return parent::buildForm($form,$form_state);
  }

  /**
   * Form submission handler.
   *
   *  $form -> An associative array containing the structure of the form.
   *  $form_state -> An associative array containing the current state of the form.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // switch to db based config
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('ldaprdn', $form_state->getValue('ldaprdn'))
      ->set('ldappass', $form_state->getValue('ldappass'))
      ->save();
  }
}
