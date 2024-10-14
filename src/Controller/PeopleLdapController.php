<?php

namespace Drupal\as_people_ldap\Controller;

use Drupal\Core\Controller\ControllerBase;


class PeopleLdapController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content($netid) {
    //$markup = $netid;
    $info= as_people_ldap_get_netid_ldap($netid);
    $markup = $info[0]["cornelleducampusaddress"][0] .'<br />';
    $markup = $markup. '<a href="mailto:'.$info[0]["mail"][0].'">'.$info[0]["mail"][0].'</a><br />';
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('<h1>LDAP Data for '.$netid.'</h1><div class="slides">
<article class="slide-aside">'.$markup.'</article></div>'),
    );
  }

}
