<?php
/**
 * NOTE: Database settings are always handled elsewhere; either by
 * - Pantheon's settings
 * - DDEV's settings
 * - settings.local.php (though not specified currently)
 */

// Hash salt.
$settings['hash_salt'] = 'Uz4yLyQIgql9acJMeygTwcVCo7zfdMZ68iRY2p417kLAZ8bN5pSutJ2vbtok4PrHJ2LCxW4veg';

/**
 * Access control for update.php script.
 *
 */
$settings['update_free_access'] = FALSE;

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * Trusted host configuration.
 */
$settings['trusted_host_patterns'] = [
  '^unedev',
  'une.edu$',
  '^une.r',
  '^une8.r',
  '^.+unecomm\.pantheonsite\.io$',
];

/**
 * The default list of directories that will be ignored by Drupal's file API.
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * The default number of entities to update in a batch process.
 */
$settings['entity_update_batch_size'] = 50;


/**
 * Include the Pantheon-specific settings file.
 *
 * N.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 *
 * NOTE: this MUST happen AFTER loading settings.pantheon.php.
 */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

// Config split settings. In production, disable the development split.
// Disable the pantheon split; this will come on down below if we're in Pantheon.
$config['config_split.config_split.development_settings']['status'] = FALSE;
$config['config_split.config_split.pantheon']['status'] = FALSE;

/**
  * Evnironment Indicator logic
  * https://www.drupal.org/project/environment_indicator
  * https://pantheon.io/docs/environment-indicator.
  */
if (!defined('PANTHEON_ENVIRONMENT')) {
  $config['environment_indicator.indicator']['name'] = 'Local';
  $config['environment_indicator.indicator']['bg_color'] = '#808080';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}

// Pantheon Env Specific Config.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  // Turn on pantheon config split.
  $config['config_split.config_split.pantheon']['status'] = TRUE;

  /**
   * LDAP Configuration.
   */
  // Pantheon Secure Integration overrides.
  $config['ldap_servers.server.une_ldapserver']['address'] = 'ldaps://127.0.0.1:' . PANTHEON_SOIP_LDAP_NEW;
  $config['ldap_servers.server.une_ldapserver']['port'] = PANTHEON_SOIP_LDAP_NEW;

  ldap_set_option(NULL, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option(NULL, LDAP_OPT_REFERRALS, 0);
  ldap_set_option(NULL, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_ALLOW);

  $ldap_cacert = getcwd() . "/../../../all.pem";
  putenv("LDAPTLS_CACERT=$ldap_cacert");
  putenv('LDAPTLS_REQCERT=never');

  switch ($_ENV['PANTHEON_ENVIRONMENT']) {
    case 'dev':
      $config['environment_indicator.indicator']['name'] = 'Dev';
      $config['environment_indicator.indicator']['bg_color'] = '#d25e0f';
      $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
      break;

    case 'test':
      $config['environment_indicator.indicator']['name'] = 'Test';
      $config['environment_indicator.indicator']['bg_color'] = '#c50707';
      $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
      break;

    case 'live':
      $config['environment_indicator.indicator']['name'] = 'Live!';
      $config['environment_indicator.indicator']['bg_color'] = '#4C742C';
      $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
      break;

    default:
      // Multidev catchall.
      $config['environment_indicator.indicator']['name'] = 'Multidev';
      $config['environment_indicator.indicator']['bg_color'] = '#efd01b';
      $config['environment_indicator.indicator']['fg_color'] = '#000000';
      break;
  }
}

/**
 * If there is a local settings file, then include it.
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * Primary Domain settings, for Pantheon.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment.
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    // Replace www.example.com with your registered domain name.
    $primary_domain = 'www.une.edu';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
  }

  $requires_redirect = FALSE;

  // Ensure the site is being served from the primary domain.
  if ($_SERVER['HTTP_HOST'] != $primary_domain) {
    $requires_redirect = TRUE;
  }

  // If you're not using HSTS in the pantheon.yml file, uncomment this next block.
  // if (!isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
  //     || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON') {
  //   $requires_redirect = TRUE;
  // }.
  if ($requires_redirect === TRUE) {

    // Name transaction "redirect" in New Relic for improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://' . $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Drupal 8 Trusted Host Settings.
  if (is_array($settings)) {
    $settings['trusted_host_patterns'] = ['^' . preg_quote($primary_domain) . '$'];
  }
}

// Legacy redirects.
include_once 'redirects.php';

// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

// For catalog cloner. Since this loads the default database as a reference
// to itself, it must be dead last, since we need to be sure that whatever
// is providing the database is loaded (Pantheon, DDEV, whatever).
$databases['self']['default'] = $databases['default']['default'];
