<?php

namespace Drupal\as_people_ldap\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\as_people_ldap\Service\LdapApiService;
use Drupal\as_people_ldap\Service\LdapFormatterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an LDAP People Block.
 *
 * @Block(
 *   id = "ldap_block",
 *   admin_label = @Translation("LDAP Block"),
 *   category = @Translation("People"),
 * )
 */
class ASLdap extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The LDAP API service.
   *
   * @var \Drupal\as_people_ldap\Service\LdapApiService
   */
  protected $ldapApi;

  /**
   * The LDAP formatter service.
   *
   * @var \Drupal\as_people_ldap\Service\LdapFormatterService
   */
  protected $ldapFormatter;

  /**
   * Constructs an ASLdap block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\as_people_ldap\Service\LdapApiService $ldap_api
   *   The LDAP API service.
   * @param \Drupal\as_people_ldap\Service\LdapFormatterService $ldap_formatter
   *   The LDAP formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LdapApiService $ldap_api, LdapFormatterService $ldap_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ldapApi = $ldap_api;
    $this->ldapFormatter = $ldap_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('as_people_ldap.api'),
      $container->get('as_people_ldap.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $netid = '';
    $config = $this->getConfiguration();

    if (!empty($config['netid'])) {
      $netid = $config['netid'];
    }

    $info = $this->ldapApi->getNetIdLdap($netid);
    $markup = $this->ldapFormatter->formatLdapDataAsMarkup($info);

    if (!empty($markup)) {
      $build['ldap_block']['#markup'] = $markup;
    }

    return $build;
  }

}

