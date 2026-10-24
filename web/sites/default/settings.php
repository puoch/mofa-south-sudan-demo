<?php

/**
 * @file
 * Drupal settings for the MoFA WEBSITE.
 *
 * No secrets live in this file. Every environment-specific value comes from
 * environment variables (see .env.example) or from an untracked
 * settings.local.php. Production values must come from the hosting
 * platform's secrets manager, never from version control.
 */

$databases['default']['default'] = [
  'driver' => 'pgsql',
  'database' => getenv('DB_NAME') ?: 'mofa',
  'username' => getenv('DB_USER') ?: 'mofa',
  'password' => getenv('DB_PASSWORD') ?: '',
  'host' => getenv('DB_HOST') ?: '127.0.0.1',
  'port' => getenv('DB_PORT') ?: '5432',
  'prefix' => '',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];

$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: '';
$settings['config_sync_directory'] = '../config/sync';
$settings['file_scan_ignore_directories'] = ['node_modules', 'bower_components'];
$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;
$settings['migrate_node_migrate_type_classic'] = FALSE;

// Only answer requests for known host names (comma-separated regex list).
if ($hosts = getenv('DRUPAL_TRUSTED_HOSTS')) {
  $settings['trusted_host_patterns'] = array_map('trim', explode(',', $hosts));
}

// Never show errors to visitors unless an environment opts in.
$config['system.logging']['error_level'] = getenv('DRUPAL_ERROR_LEVEL') ?: 'hide';

// This site is a development demonstration: keep it out of search engines.
$settings['mofa_environment'] = getenv('MOFA_ENVIRONMENT') ?: 'development';

if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') === 'true') {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
