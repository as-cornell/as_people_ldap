<?php

namespace Drupal\as_people_ldap\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for querying Cornell LDAP directory.
 *
 * @package Drupal\as_people_ldap\Service
 */
class LdapApiService {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an LdapApiService object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(CacheBackendInterface $cache, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->cache = $cache;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Gets LDAP data for a given NetID with caching.
   *
   * @param string $netid
   *   The Cornell NetID to look up.
   *
   * @return array
   *   Array of LDAP data or empty array if not found.
   */
  public function getNetIdLdap($netid) {
    // Declare empty arrays.
    $settings = [];
    $ldapdata = [];

    // Set cache id.
    $cid = 'as_people_ldap:' . $netid;

    // Generate a random number between 4 and 6 days (in seconds) to provide
    // cache life variable.
    $clife = rand(345600, 518400);

    // Get ldaprdn and ldappass from config.
    $config = $this->configFactory->get("as_people_ldap.settings");

    // Debug ONLY when in lando/dev environment AND debug mode is enabled in config.
    $showdebug = FALSE;
    if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT == 'lando' || PANTHEON_ENVIRONMENT == 'dev') && $config->get('debug_mode')) {
      $showdebug = TRUE;
    }

    // Put settings into array.
    $settings = [
      'directory' => [
        'host' => 'ldaps://query.directory.cornell.edu:636/',
        'port' => '636',
        'bind_rdn' => $config->get("ldaprdn"),
        'bind_password' => $config->get("ldappass"),
        'display_password' => 'XxXxXxX',
        'base_dn' => 'ou=People,o=Cornell University,c=US',
        'filter' => '(uid=' . $netid . ')',
        'attributes' => ['cn', 'cornelleducampusaddress', 'cornelledupublishedemail', 'cornelleducampusphone'],
      ],
    ];

    // Debug LDAP TLS settings.
    if ($showdebug) {
      dump('LDAPTLS_CACERT=' . getenv('LDAPTLS_CACERT'));
      if (getenv('LDAPTLS_CACERT')) {
        dump(' hash: ' . exec('openssl x509 -noout -hash -in ' . getenv('LDAPTLS_CACERT')));
      }
      dump('LDAPTLS_CACERTDIR=' . getenv('LDAPTLS_CACERTDIR'));
      dump('LDAPTLS_REQCERT=' . getenv('LDAPTLS_REQCERT'));
    }

    foreach ($settings as $host => $setting) {
      if ($showdebug) {
        dump("Attempting to connect to {$setting['host']} on port {$setting['port']}.");
      }

      $resolved_port = $setting['port'];
      if (!is_numeric($resolved_port)) {
        // If it's a string, then attempt to use it as the name of a PHP
        // constant.
        $resolved_port = constant($resolved_port);
      }

      $resolved_address = $setting['host'];
      // PHP ldap_connect function ignores the port option if scheme is
      // included in the host, so we must append port number to the 'address'.
      if (strpos($resolved_address, 'ldap') !== FALSE) {
        $resolved_address = $resolved_address . ":" . $resolved_port;
      }

      // Check cache first.
      if ($cache = $this->cache->get($cid)) {
        if ($showdebug) {
          dump('Cache found.');
        }
        // Fetch cache data.
        $ldapdata = $cache->data;
        if ($showdebug) {
          echo('<p>cached LDAP entry:<br>');
          var_dump($ldapdata);
          echo('</p>');
        }
      }
      else {
        if ($showdebug) {
          dump('No cache found.');
        }

        // Connect to LDAP server.
        $link_identifier = ldap_connect($resolved_address);

        if (!$link_identifier) {
          if ($showdebug) {
            echo 'Unable to connect - ' . ldap_error($link_identifier) . PHP_EOL;
          }
          $this->logger->error('Unable to connect to LDAP server: @error', [
            '@error' => ldap_error($link_identifier),
          ]);
          continue;
        }

        if ($showdebug) {
          dump('Connected.');
          dump("Attempting to bind with rdn {$setting['bind_rdn']} and password {$setting['display_password']}.");
        }

        // Bind to LDAP server.
        if (!ldap_bind($link_identifier, $setting['bind_rdn'], $setting['bind_password'])) {
          if ($showdebug) {
            dump('Unable to bind - ' . ldap_error($link_identifier));
          }
          $this->logger->error('Unable to bind to LDAP server: @error', [
            '@error' => ldap_error($link_identifier),
          ]);
          ldap_unbind($link_identifier);
          continue;
        }

        if ($showdebug) {
          dump('Bind succeeded.');
          dump("Attempting to search with base_dn {$setting['base_dn']}, filter {$setting['filter']} and attributes " . var_export($setting['attributes'], TRUE));
        }

        // Search LDAP directory.
        $search_result_identifier = ldap_search($link_identifier, $setting['base_dn'], $setting['filter'], $setting['attributes']);
        if (!$search_result_identifier) {
          if ($showdebug) {
            echo 'Unable to search - ' . ldap_error($link_identifier) . PHP_EOL;
          }
          $this->logger->error('Unable to search LDAP directory: @error', [
            '@error' => ldap_error($link_identifier),
          ]);
          ldap_unbind($link_identifier);
          continue;
        }

        if ($showdebug) {
          dump('Search succeeded.');
        }

        // Get LDAP data for this entry.
        $ldapdata = ldap_get_entries($link_identifier, $search_result_identifier);
        if ($showdebug) {
          echo('<p>fresh LDAP entry:<br>');
          var_dump($ldapdata);
          echo('</p>');
        }

        // Only cache if valid Cornell email found.
        if (!empty($ldapdata[0]['cornelledupublishedemail'][0]) && str_contains($ldapdata[0]['cornelledupublishedemail'][0], 'cornell.edu')) {
          // Set cache.
          $this->cache->set($cid, $ldapdata, time() + $clife);
        }
      }
    }

    return $ldapdata;
  }

}
