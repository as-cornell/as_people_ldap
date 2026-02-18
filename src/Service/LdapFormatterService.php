<?php

namespace Drupal\as_people_ldap\Service;

/**
 * Service for formatting LDAP data.
 *
 * @package Drupal\as_people_ldap\Service
 */
class LdapFormatterService {

  /**
   * Formats LDAP data as HTML markup.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   *
   * @return string
   *   HTML markup for display.
   */
  public function formatLdapDataAsMarkup(array $ldap_data) {
    $markup = '';

    if (empty($ldap_data[0])) {
      return $markup;
    }

    // Campus address.
    if (!empty($ldap_data[0]['cornelleducampusaddress'][0])) {
      $markup .= $ldap_data[0]['cornelleducampusaddress'][0] . '<br />';
    }

    // Email.
    if (!empty($ldap_data[0]['cornelledupublishedemail'][0])) {
      $email = $ldap_data[0]['cornelledupublishedemail'][0];
      $markup .= '<a href="mailto:' . $email . '">' . $email . '</a><br />';
    }

    // Phone.
    if (!empty($ldap_data[0]['cornelleducampusphone'][0])) {
      $markup .= $ldap_data[0]['cornelleducampusphone'][0] . '<br />';
    }

    return $markup;
  }

  /**
   * Gets a specific field value from LDAP data.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   * @param string $field
   *   The LDAP field name to retrieve.
   * @param int $index
   *   The index of the value to retrieve (default: 0).
   *
   * @return string|null
   *   The field value or NULL if not found.
   */
  public function getLdapField(array $ldap_data, $field, $index = 0) {
    if (!empty($ldap_data[0][$field][$index])) {
      return $ldap_data[0][$field][$index];
    }
    return NULL;
  }

  /**
   * Gets the campus address from LDAP data.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   *
   * @return string|null
   *   The campus address or NULL if not found.
   */
  public function getCampusAddress(array $ldap_data) {
    return $this->getLdapField($ldap_data, 'cornelleducampusaddress');
  }

  /**
   * Gets the email address from LDAP data.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   *
   * @return string|null
   *   The email address or NULL if not found.
   */
  public function getEmail(array $ldap_data) {
    return $this->getLdapField($ldap_data, 'cornelledupublishedemail');
  }

  /**
   * Gets the campus phone number from LDAP data.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   *
   * @return string|null
   *   The phone number or NULL if not found.
   */
  public function getPhone(array $ldap_data) {
    return $this->getLdapField($ldap_data, 'cornelleducampusphone');
  }

  /**
   * Gets the common name (cn) from LDAP data.
   *
   * @param array $ldap_data
   *   LDAP data array from Cornell directory.
   *
   * @return string|null
   *   The common name or NULL if not found.
   */
  public function getCommonName(array $ldap_data) {
    return $this->getLdapField($ldap_data, 'cn');
  }

}
