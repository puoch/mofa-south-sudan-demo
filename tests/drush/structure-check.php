<?php

/**
 * @file
 * Checks the content model, workflow and permissions after an install.
 *
 * Run: vendor/bin/drush php:script tests/drush/structure-check.php
 * Throws (non-zero exit) on any failure so CI fails.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

$failures = [];
$checks = 0;
$check = function (bool $ok, string $message) use (&$failures, &$checks) {
  $checks++;
  if (!$ok) {
    $failures[] = $message;
  }
};

$types = ['page', 'national_profile', 'leader_profile', 'mission', 'news_item', 'publication', 'service', 'archive_record'];
$editorial = ['field_placeholder', 'field_sources', 'field_source_notes', 'field_last_reviewed', 'field_content_owner'];

foreach ($types as $type) {
  $check((bool) NodeType::load($type), "Content type $type exists");
  foreach ($editorial as $field) {
    $check((bool) FieldConfig::loadByName('node', $type, $field), "$type has $field");
  }
  $placeholder = FieldConfig::loadByName('node', $type, 'field_placeholder');
  $check($placeholder && ($placeholder->getDefaultValueLiteral()[0]['value'] ?? 0) == 1, "$type defaults to placeholder = on");
  $settings = \Drupal::service('content_translation.manager')->isEnabled('node', $type);
  $check($settings, "$type is translatable (Arabic-ready)");
}

$check(FieldConfig::loadByName('node', 'mission', 'field_jurisdiction_status')?->getDefaultValueLiteral()[0]['value'] === 'placeholder', 'Missions default to jurisdiction status "placeholder"');
$check(FieldConfig::loadByName('node', 'service', 'field_service_status')?->getDefaultValueLiteral()[0]['value'] === 'information', 'Services default to "Information only"');

// Workflow.
$workflow = Workflow::load('editorial');
$plugin = $workflow->getTypePlugin();
foreach (['draft', 'review', 'approved', 'published', 'archived'] as $state) {
  $check($plugin->hasState($state), "Workflow has state $state");
}
$check(!$plugin->getState('approved')->isPublishedState(), 'Approved is not yet published');
$check(!$plugin->getState('draft')->canTransitionTo('published'), 'Draft cannot go straight to Published');
$check(!$plugin->getState('review')->canTransitionTo('published'), 'In review cannot go straight to Published');
foreach ($types as $type) {
  $check($plugin->appliesToEntityTypeAndBundle('node', $type), "Workflow applies to $type");
}

// Separation of duties.
$author = Role::load('content_author');
$reviewer = Role::load('content_reviewer');
$publisher = Role::load('content_publisher');
$check(!$author->hasPermission('use editorial transition publish'), 'Authors cannot publish');
$check(!$author->hasPermission('use editorial transition approve'), 'Authors cannot approve');
$check(!$reviewer->hasPermission('use editorial transition publish'), 'Reviewers cannot publish');
$check($reviewer->hasPermission('use editorial transition approve'), 'Reviewers can approve');
$check($publisher->hasPermission('use editorial transition publish'), 'Publishers can publish');
foreach (['content_author', 'content_reviewer', 'content_publisher', 'site_manager'] as $rid) {
  $role = Role::load($rid);
  foreach (['administer users', 'administer permissions', 'administer site configuration', 'administer modules'] as $p) {
    $check(!$role->hasPermission($p), "$rid does not have '$p'");
  }
}
$anon = Role::load('anonymous');
foreach ($anon->getPermissions() as $p) {
  $check(in_array($p, ['access content', 'view media', 'view mofa styleguide'], TRUE), "Anonymous permission '$p' is expected");
}
$check(\Drupal::config('user.settings')->get('register') === 'admin_only', 'Visitors cannot create accounts');

// Languages: English only at launch.
$langs = array_keys(\Drupal::languageManager()->getLanguages());
$check($langs === ['en'], 'Only English is enabled (found: ' . implode(',', $langs) . ')');

// Registration must not exist in Phase 1/2.
$check(!\Drupal::moduleHandler()->moduleExists('webform'), 'No form-builder module is installed');
$check(!NodeType::load('registration'), 'No registration content type exists');

foreach ($failures as $f) {
  fwrite(STDERR, "FAIL: $f\n");
}
printf("%d checks, %d failed\n", $checks, count($failures));
if ($failures) {
  throw new \RuntimeException(count($failures) . ' structure check(s) failed.');
}
