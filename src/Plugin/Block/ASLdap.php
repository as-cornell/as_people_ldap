<?php

namespace Drupal\as_people_ldap\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a Current Events Block.
 *
 * @Block(
 *   id = "ldap_block",
 *   admin_label = @Translation("LDAP Block"),
 *   category = @Translation("People"),
 * )
 */
class ASLdap extends BlockBase implements BlockPluginInterface {


  /**
   * {@inheritdoc}
   */


  public function build() {

    $build = [];
    $markup = '';
    $netid = '';
    $config = $this->getConfiguration();

    if (!empty($config['netid'])) {
      $netid = $config['netid'] ;
    }

    $info = as_people_ldap_get_netid_ldap($netid);

    if (!empty($info[0])) {
      if (!empty($info[0]['cornelleducampusaddress'][0])) {
        $markup = $info[0]['cornelleducampusaddress'][0] .'<br />';
          }
      if (!empty($info[0]['cornelledupublishedemail'][0])) {
        $markup = $markup. '<a href="mailto:'.$info[0]['cornelledupublishedemail'][0].'">'.$info[0]['cornelledupublishedemail'][0].'</a><br />';
          }
      if (!empty($info[0]['cornelleducampusphone'][0])) {
        $markup = $markup. $info[0]['cornelleducampusphone'][0].'<br />';
          }
        //$build['events_block']['#markup'] = $netid;
        $build['ldap_block']['#markup'] = $markup;
    //} else { // There was no LDAP data
      //$build['ldap_block']['#markup'] = '<main>
                //<p>No LDAP data for '.$netid.'</p>
                //</main>';
    }

    return $build;
  }
}
