[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_people_ldap/v)](https://packagist.org/packages/as-cornell/as_people_ldap)
# AS PEOPLE LDAP (as_people_ldap)

## INTRODUCTION

Displays LDAP people data from directory.cornell.edu by NetID in a block.

## MAINTAINERS

Current maintainers for Drupal 10:

- Mark Wilson (markewilson)

## CONFIGURATION
- Enable the module as you would any other module
- Configure the global module settings: /admin/config/services/as-people-ldap-settings
- Test route available: /people_ldap/{netid}
- Includes block: ldap_block

## FUNCTIONS
- as_people_ldap_get_netid_ldap

## INTEGRATION WITH LDAPS
- Put the following in settings.php

// LDAP - specify file that contains the TLS CA Certificate.
// Can also be used to provide intermediate certificate to trust remote servers.
$tls_cacert = DRUPAL_ROOT . '/sites/default/files/private/certs/ca.crt';
if (!file_exists($tls_cacert)) die($tls_cacert . ' CA cert does not exist');
putenv("LDAPTLS_CACERT=$tls_cacert");


// LDAP - specify file that contains the client certificate.
$tls_cert = DRUPAL_ROOT . '/sites/default/files/private/certs/client.crt';
if (!file_exists($tls_cert)) die($tls_cert . ' client cert does not exist');
putenv("LDAPTLS_CERT=$tls_cert");


// LDAP - specify file that contains private key w/o password for TLS_CERT.
$tls_key = DRUPAL_ROOT . '/sites/default/files/private/certs/client.pem';
if (!file_exists($tls_key)) die($tls_key . ' client key does not exist');
putenv("LDAPTLS_KEY=$tls_key");

//LDAP - specify cert directory
$tls_cert_dir = DRUPAL_ROOT . '/sites/default/files/private/certs/';
putenv("LDAPTLS_CACERTDIR=$tls_cert_dir");

// LDAP - Allow server certificate check in a TLS session.
putenv('LDAPTLS_REQCERT=allow');


- move /certs to /web/sites/default/files/private with sftp
- cd web/sites/default/files/private
- conncet via sftp
- cd files/private
- put -r certs certs
- exit