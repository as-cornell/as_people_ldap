[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_people_ldap/v)](https://packagist.org/packages/as-cornell/as_people_ldap)

# AS People LDAP (as_people_ldap)

Provides Cornell LDAP directory integration for Drupal 10 sites.

## Table of Contents

- [Introduction](#introduction)
- [Architecture](#architecture)
- [Services](#services)
- [Usage](#usage)
- [Blocks](#blocks)
- [Routes](#routes)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [SSL/TLS Certificate Setup](#ssltls-certificate-setup)
- [Troubleshooting](#troubleshooting)
- [Maintainers](#maintainers)

## Introduction

The AS People LDAP module fetches and displays people data from Cornell's LDAP directory (directory.cornell.edu) by NetID. It provides:

- **Service-based architecture** with dependency injection
- **Automatic caching** (4-6 day random cache TTL)
- **Block plugin** for displaying LDAP data
- **Controller** with test route for NetID lookups
- **Secure LDAPS** connection with certificate support
- **Flexible formatting** utilities for LDAP data

## Architecture

The module uses an **object-oriented architecture** with separate service classes:

```
as_people_ldap/
├── src/
│   ├── Service/
│   │   ├── LdapApiService.php         # LDAP connections and caching
│   │   └── LdapFormatterService.php   # Data formatting utilities
│   ├── Controller/
│   │   └── PeopleLdapController.php   # Route controller
│   ├── Plugin/Block/
│   │   └── ASLdap.php                 # LDAP block
│   └── Form/
│       └── AsPeopleLdapSettingsForm.php # Settings form
├── as_people_ldap.services.yml        # Service definitions
└── as_people_ldap.module              # Hooks only
```

**Key Benefits:**
- ✅ Testable service classes
- ✅ Reusable via dependency injection
- ✅ Clear separation of concerns
- ✅ Follows Drupal 10 best practices

## Services

### LdapApiService

Handles LDAP communication with Cornell directory and manages caching.

**Service ID:** `as_people_ldap.api`

**Dependencies:**
- `@cache.data` - Cache backend
- `@config.factory` - Config factory
- `@logger.channel.as_people_ldap` - Logger channel

**Methods:**

#### `getNetIdLdap($netid)`

Fetches LDAP data for a given Cornell NetID with automatic caching.

**Parameters:**
- `$netid` (string) - The Cornell NetID to look up

**Returns:** `array` - Array of LDAP data or empty array if not found

**Example:**
```php
$ldap_api = \Drupal::service('as_people_ldap.api');
$data = $ldap_api->getNetIdLdap('abc123');
```

**LDAP Query Details:**
- **Host:** `ldaps://query.directory.cornell.edu:636/`
- **Base DN:** `ou=People,o=Cornell University,c=US`
- **Filter:** `(uid={netid})`
- **Attributes:** `cn`, `cornelleducampusaddress`, `cornelledupublishedemail`, `cornelleducampusphone`

**Caching:**
- Cache ID: `as_people_ldap:{netid}`
- Cache Duration: Random 4-6 days (345600-518400 seconds)
- Cache Bin: `cache.data`
- Only caches if valid Cornell email found

**Debug Mode:**
- Enabled on `lando` and `dev` environments
- Shows connection, bind, and search details
- Displays TLS certificate information

### LdapFormatterService

Provides utilities for formatting LDAP data.

**Service ID:** `as_people_ldap.formatter`

**Methods:**

#### `formatLdapDataAsMarkup(array $ldap_data)`

Formats LDAP data as HTML markup.

**Parameters:**
- `$ldap_data` (array) - LDAP data array from Cornell directory

**Returns:** `string` - HTML markup for display

**Example:**
```php
$formatter = \Drupal::service('as_people_ldap.formatter');
$markup = $formatter->formatLdapDataAsMarkup($ldap_data);
```

**Generated Markup Includes:**
- Campus address
- Email (as mailto link)
- Phone number

#### `getLdapField(array $ldap_data, $field, $index = 0)`

Gets a specific field value from LDAP data.

**Parameters:**
- `$ldap_data` (array) - LDAP data array
- `$field` (string) - The LDAP field name to retrieve
- `$index` (int) - The index of the value (default: 0)

**Returns:** `string|null` - The field value or NULL if not found

#### `getCampusAddress(array $ldap_data)`

Gets the campus address from LDAP data.

**Returns:** `string|null`

#### `getEmail(array $ldap_data)`

Gets the email address from LDAP data.

**Returns:** `string|null`

#### `getPhone(array $ldap_data)`

Gets the campus phone number from LDAP data.

**Returns:** `string|null`

#### `getCommonName(array $ldap_data)`

Gets the common name (cn) from LDAP data.

**Returns:** `string|null`

## Usage

### Using Services in Custom Code

**In a Controller:**
```php
namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\as_people_ldap\Service\LdapApiService;
use Drupal\as_people_ldap\Service\LdapFormatterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyController extends ControllerBase {

  protected $ldapApi;
  protected $ldapFormatter;

  public function __construct(LdapApiService $ldap_api, LdapFormatterService $ldap_formatter) {
    $this->ldapApi = $ldap_api;
    $this->ldapFormatter = $ldap_formatter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_people_ldap.api'),
      $container->get('as_people_ldap.formatter')
    );
  }

  public function myPage($netid) {
    $data = $this->ldapApi->getNetIdLdap($netid);
    $email = $this->ldapFormatter->getEmail($data);
    // Use data...
  }
}
```

**In a Service:**
```php
# my_module.services.yml
services:
  my_module.my_service:
    class: Drupal\my_module\MyService
    arguments: ['@as_people_ldap.api', '@as_people_ldap.formatter']
```

### Accessing Individual Fields

```php
$ldap_api = \Drupal::service('as_people_ldap.api');
$formatter = \Drupal::service('as_people_ldap.formatter');

$data = $ldap_api->getNetIdLdap('abc123');

$name = $formatter->getCommonName($data);
$email = $formatter->getEmail($data);
$phone = $formatter->getPhone($data);
$address = $formatter->getCampusAddress($data);
```

## Blocks

### LDAP Block

**Block ID:** `ldap_block`
**Admin Label:** "LDAP Block"
**Category:** "People"

Displays LDAP data for a configured NetID.

**Configuration:**
- `netid` - Cornell NetID to display

**Usage:**
1. Navigate to `/admin/structure/block`
2. Click "Place block"
3. Search for "LDAP Block"
4. Configure NetID
5. Save

## Routes

### Test Route

**Route:** `/people_ldap/{netid}`

Displays LDAP data for a given NetID in a test page.

**Example:**
- `/people_ldap/abc123`

**Output:** HTML page with LDAP data including address, email, and phone.

## Requirements

- Drupal: >= 10.0
- PHP: >= 8.1
- PHP LDAP extension
- Access to Cornell LDAP directory (ldaps://query.directory.cornell.edu:636/)
- Valid LDAP credentials (bind RDN and password)
- SSL/TLS certificates for LDAPS connection

**Drupal Modules:**
- No additional module dependencies

## Installation

### Via Composer (Recommended)

```bash
composer require as-cornell/as_people_ldap
drush en as_people_ldap -y
drush cr
```

### Manual Installation

1. Download the module to `/modules/custom/as_people_ldap`
2. Enable the module: `drush en as_people_ldap -y`
3. Clear cache: `drush cr`

## Configuration

### Module Settings

Navigate to: `/admin/config/services/as-people-ldap-settings`

**Required Settings:**
- **LDAP RDN or DN** - Full distinguished name for LDAP bind (e.g., `uid=myapp,ou=apps,o=Cornell University,c=US`)
- **LDAP Password** - Password for LDAP bind

**Optional Settings:**
- **Enable debug mode** - Checkbox to enable verbose debugging output

**Example Configuration:**
```
LDAP RDN: uid=drupal-ldap,ou=applications,o=Cornell University,c=US
LDAP Password: [secure password]
Debug Mode: ☐ (unchecked for production)
```

### Debug Mode

Debug mode provides verbose output for troubleshooting LDAP connections and queries.

**When debug mode is enabled, the following information is displayed:**
- LDAP connection attempts and results
- Bind authentication attempts
- Search queries with filters and attributes
- TLS/SSL certificate information (LDAPTLS_CACERT, LDAPTLS_CERT, etc.)
- Cache hits vs. fresh LDAP queries
- Full LDAP response data

**Requirements for debug mode:**

Debug mode requires **BOTH** of these conditions to be true:
1. Environment must be `lando` (local) OR `dev` (Pantheon dev)
2. **AND** the "Enable debug mode" checkbox must be checked in settings

**To enable debug mode:**

1. **Via Settings Form:**
   - Navigate to `/admin/config/services/as-people-ldap-settings`
   - Check "Enable debug mode"
   - Save configuration
   - **Note:** Debug output will only appear in lando/dev environments

2. **Via Drush:**
   ```bash
   drush config:set as_people_ldap.settings debug_mode 1 -y
   drush cr
   ```

**Security:** Debug mode will **NOT** work in production environments (test, live), even if the checkbox is enabled. This prevents accidental exposure of sensitive information in production.

**To disable debug mode:**
```bash
drush config:set as_people_ldap.settings debug_mode 0 -y
drush cr
```

### Configuration via Drush

```bash
# Set LDAP credentials
drush config:set as_people_ldap.settings ldaprdn 'uid=myapp,ou=apps,o=Cornell University,c=US' -y
drush config:set as_people_ldap.settings ldappass 'your-password' -y

# Enable debug mode
drush config:set as_people_ldap.settings debug_mode 1 -y

# Disable debug mode
drush config:set as_people_ldap.settings debug_mode 0 -y

# Clear cache after configuration changes
drush cr
```

### Cache Settings

LDAP data is cached for a **random 4-6 days** in the `cache.data` bin.

**To clear LDAP cache:**
```bash
# Clear all cache
drush cr

# Or manually clear specific cache ID
drush php-eval "\Drupal::cache('data')->delete('as_people_ldap:abc123');"
```

### Logging

The module provides its own logger channel: `logger.channel.as_people_ldap`

**View logs:**
```bash
drush watchdog:show --type=as_people_ldap
```

## SSL/TLS Certificate Setup

Cornell's LDAP directory requires LDAPS (LDAP over SSL/TLS) with client certificates.

### Certificate Files Required

Place the following files in `/sites/default/files/private/certs/`:

- `ca.crt` - CA Certificate
- `client.crt` - Client Certificate
- `client.pem` - Client Private Key (without password)

### Settings.php Configuration

Add the following to your `settings.php`:

```php
// LDAP - Specify file that contains the TLS CA Certificate.
// Can also be used to provide intermediate certificate to trust remote servers.
$tls_cacert = DRUPAL_ROOT . '/sites/default/files/private/certs/ca.crt';
if (!file_exists($tls_cacert)) {
  die($tls_cacert . ' CA cert does not exist');
}
putenv("LDAPTLS_CACERT=$tls_cacert");

// LDAP - Specify file that contains the client certificate.
$tls_cert = DRUPAL_ROOT . '/sites/default/files/private/certs/client.crt';
if (!file_exists($tls_cert)) {
  die($tls_cert . ' client cert does not exist');
}
putenv("LDAPTLS_CERT=$tls_cert");

// LDAP - Specify file that contains private key w/o password for TLS_CERT.
$tls_key = DRUPAL_ROOT . '/sites/default/files/private/certs/client.pem';
if (!file_exists($tls_key)) {
  die($tls_key . ' client key does not exist');
}
putenv("LDAPTLS_KEY=$tls_key");

// LDAP - Specify cert directory.
$tls_cert_dir = DRUPAL_ROOT . '/sites/default/files/private/certs/';
putenv("LDAPTLS_CACERTDIR=$tls_cert_dir");

// LDAP - Allow server certificate check in a TLS session.
putenv('LDAPTLS_REQCERT=allow');
```

### Deploying Certificates via SFTP

```bash
# Connect to server via SFTP
sftp user@server

# Navigate to private files directory
cd web/sites/default/files/private

# Upload certificates directory
put -r certs certs

# Exit
exit
```

### Verify Certificate Setup

```bash
# Check environment variables
drush php-eval "
echo 'LDAPTLS_CACERT: ' . getenv('LDAPTLS_CACERT') . PHP_EOL;
echo 'LDAPTLS_CERT: ' . getenv('LDAPTLS_CERT') . PHP_EOL;
echo 'LDAPTLS_KEY: ' . getenv('LDAPTLS_KEY') . PHP_EOL;
echo 'LDAPTLS_CACERTDIR: ' . getenv('LDAPTLS_CACERTDIR') . PHP_EOL;
echo 'LDAPTLS_REQCERT: ' . getenv('LDAPTLS_REQCERT') . PHP_EOL;
"

# Check if files exist
ls -l web/sites/default/files/private/certs/
```

## Troubleshooting

### LDAP Connection Issues

1. **Check PHP LDAP extension:**
   ```bash
   php -m | grep ldap
   ```

2. **Check credentials:**
   ```bash
   drush config:get as_people_ldap.settings
   ```

3. **Test LDAP connection:**
   ```bash
   drush php-eval "
   \$api = \Drupal::service('as_people_ldap.api');
   \$data = \$api->getNetIdLdap('abc123');
   print_r(\$data);
   "
   ```

4. **Check logs:**
   ```bash
   drush watchdog:show --type=as_people_ldap --count=20
   ```

### Certificate Issues

**Error: "CA cert does not exist"**
- Verify certificate files are in `/sites/default/files/private/certs/`
- Check file permissions (should be readable by web server)
- Verify path in `settings.php` is correct

**Error: "Unable to connect"**
- Check firewall allows outbound LDAPS (port 636)
- Verify server can reach `query.directory.cornell.edu`
- Check certificate validity dates

**Error: "Unable to bind"**
- Verify LDAP RDN is correct full distinguished name
- Check LDAP password is correct
- Ensure credentials have permission to query directory

### Cache Issues

**Stale data displayed:**
```bash
# Clear specific NetID cache
drush php-eval "\Drupal::cache('data')->delete('as_people_ldap:abc123');"

# Clear all LDAP cache
drush php-eval "
\$cache = \Drupal::cache('data');
\$cache->deleteMultiple(\$cache->getMultiple(['as_people_ldap:']));
"
```

### Enabling Debug Output

Debug information requires **BOTH** conditions to be met:
1. Running in `lando` or `dev` environment
2. Debug mode checkbox is enabled in settings

**To enable debug output:**

1. **Via Settings Form (Recommended):**
   - Navigate to `/admin/config/services/as-people-ldap-settings`
   - Check "Enable debug mode"
   - Save configuration
   - Clear cache: `drush cr`
   - **Important:** Debug will only show output in lando/dev environments

2. **Via Drush:**
   ```bash
   drush config:set as_people_ldap.settings debug_mode 1 -y
   drush cr
   ```

**Debug output includes:**
- Connection attempts to LDAP server
- Bind authentication results
- Search query details (base DN, filter, attributes)
- TLS/SSL certificate paths and hashes
- Cache hit/miss information
- Full LDAP response data

**Security Feature:** Debug mode will NOT work in production environments (test, live), even if enabled in settings. This prevents accidental exposure of sensitive information:
- LDAP server details
- Certificate paths
- Query filters
- Full LDAP responses with personal data

## LDAP Data Structure

### Example LDAP Response

```php
[
  0 => [
    'cn' => [
      0 => 'John Doe',
      'count' => 1
    ],
    'cornelleducampusaddress' => [
      0 => '123 Day Hall, Ithaca, NY 14853',
      'count' => 1
    ],
    'cornelledupublishedemail' => [
      0 => 'jd123@cornell.edu',
      'count' => 1
    ],
    'cornelleducampusphone' => [
      0 => '607-255-1234',
      'count' => 1
    ],
    'count' => 4
  ],
  'count' => 1
]
```

## Development

### Running Tests

```bash
# PHPUnit tests (if implemented)
vendor/bin/phpunit modules/custom/as_people_ldap

# Code standards
vendor/bin/phpcs --standard=Drupal modules/custom/as_people_ldap
```

### Contributing

1. Follow Drupal coding standards
2. Add PHPUnit tests for new functionality
3. Update this README for new features
4. Use semantic versioning for releases

## Security Considerations

- ✅ LDAP credentials stored in configuration (use Key module for production)
- ✅ LDAPS (SSL/TLS) used for all connections
- ✅ Client certificates required for authentication
- ✅ Passwords never displayed in debug output
- ✅ Only caches data with valid Cornell emails
- ✅ Input sanitization on NetID parameter

**Production Recommendations:**
- Use Drupal Key module to store LDAP credentials
- Restrict access to settings form
- Monitor LDAP query logs
- Rotate certificates regularly

## Maintainers

Current maintainers for Drupal 10:

- Mark Wilson (markewilson)

## License

GPL-2.0-or-later

## Changelog

### 2.0.0 (2025-02-18)
- Refactored to OOP architecture with service classes
- Added dependency injection throughout
- Improved error handling and logging
- Enhanced documentation

### 1.x
- Initial procedural implementation
- Basic LDAP directory integration
