<?php

namespace Drupal\as_people_ldap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\as_people_ldap\Service\LdapApiService;
use Drupal\as_people_ldap\Service\LdapFormatterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying LDAP data.
 */
class PeopleLdapController extends ControllerBase {

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
   * Constructs a PeopleLdapController object.
   *
   * @param \Drupal\as_people_ldap\Service\LdapApiService $ldap_api
   *   The LDAP API service.
   * @param \Drupal\as_people_ldap\Service\LdapFormatterService $ldap_formatter
   *   The LDAP formatter service.
   */
  public function __construct(LdapApiService $ldap_api, LdapFormatterService $ldap_formatter) {
    $this->ldapApi = $ldap_api;
    $this->ldapFormatter = $ldap_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_people_ldap.api'),
      $container->get('as_people_ldap.formatter')
    );
  }

  /**
   * Display the markup.
   *
   * @param string $netid
   *   The Cornell NetID to look up.
   *
   * @return array
   *   Render array.
   */
  public function content($netid) {
    $info = $this->ldapApi->getNetIdLdap($netid);
    $markup = $this->ldapFormatter->formatLdapDataAsMarkup($info);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('<h1>LDAP Data for @netid</h1><div class="slides">
<article class="slide-aside">@markup</article></div>', [
        '@netid' => $netid,
        '@markup' => $markup,
      ]),
    ];
  }

}

