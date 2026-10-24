<?php

declare(strict_types=1);

namespace Drupal\mofa_core;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Creates or updates the Phase 2 placeholder content from YAML.
 *
 * Every node is created with "Unverified placeholder content" ticked. Items
 * are matched by path alias, so running the builder again updates them
 * instead of creating duplicates. Editors' later changes to a placeholder
 * item are overwritten on the next run, so stop running the builder once
 * real content editing begins.
 */
final class PlaceholderContentBuilder {

  /**
   * Term IDs keyed by vocabulary and term name.
   *
   * @var array<string, array<string, int>>
   */
  private array $terms = [];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $moduleList,
    private readonly AliasManagerInterface $aliasManager,
  ) {}

  /**
   * Builds terms and nodes. Returns [terms, nodes] counts.
   */
  public function build(): array {
    $file = $this->moduleList->getPath('mofa_core') . '/placeholder_content.yml';
    $data = Yaml::decode(file_get_contents($file));
    $termCount = $this->buildTerms($data['terms'] ?? []);
    $nodeCount = 0;
    foreach ($data['nodes'] ?? [] as $item) {
      $this->buildNode($item);
      $nodeCount++;
    }
    return [$termCount, $nodeCount];
  }

  /**
   * Creates vocabulary terms and their path aliases.
   */
  private function buildTerms(array $vocabularies): int {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $count = 0;
    foreach ($vocabularies as $vid => $terms) {
      foreach ($terms as $weight => $def) {
        $existing = $storage->loadByProperties(['vid' => $vid, 'name' => $def['name']]);
        $term = $existing ? reset($existing) : $storage->create(['vid' => $vid, 'name' => $def['name']]);
        $term->set('weight', $weight);
        if (!empty($def['path'])) {
          $term->set('path', ['alias' => $def['path'], 'pathauto' => 0]);
        }
        $term->save();
        $this->terms[$vid][$def['name']] = (int) $term->id();
        $count++;
      }
    }
    return $count;
  }

  /**
   * Creates or updates one node.
   */
  private function buildNode(array $item): NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');
    $node = NULL;
    $system = $this->aliasManager->getPathByAlias($item['path']);
    if (preg_match('#^/node/(\d+)$#', $system, $m)) {
      $node = $storage->load($m[1]);
    }
    if (!$node || $node->bundle() !== $item['type']) {
      $node = $storage->create(['type' => $item['type'], 'uid' => 1]);
    }

    $node->setTitle($item['title']);
    $node->set('field_placeholder', TRUE);
    $node->set('path', ['alias' => $item['path'], 'pathauto' => 0]);
    $node->set('body', ['value' => trim($item['body'] ?? ''), 'format' => 'basic_html']);
    $this->setIf($node, 'field_summary', $item['summary'] ?? NULL);
    $this->setIf($node, 'field_source_notes', isset($item['notes']) ? ['value' => $item['notes'], 'format' => 'plain_text'] : NULL);
    $this->setIf($node, 'field_content_owner', 'Placeholder: owner to be assigned');
    $this->setIf($node, 'field_context_notice', isset($item['notice']) ? ['value' => $item['notice'], 'format' => 'plain_text'] : NULL);
    $this->setIf($node, 'field_context_notice_date', $item['notice_date'] ?? NULL);
    $this->setIf($node, 'field_position_title', $item['position'] ?? NULL);
    $this->setIf($node, 'field_display_order', $item['order'] ?? NULL);
    $this->setIf($node, 'field_city', $item['city'] ?? NULL);
    $this->setIf($node, 'field_publication_date', $item['publication_date'] ?? NULL);
    $this->setIf($node, 'field_original_date', $item['original_date'] ?? NULL);
    $this->setIf($node, 'field_collection', $item['collection'] ?? NULL);
    foreach ([
      'category' => ['field_news_category', 'news_category'],
      'service_category' => ['field_service_category', 'service_category'],
      'publication_type' => ['field_publication_type', 'publication_type'],
      'mission_type' => ['field_mission_type', 'mission_type'],
    ] as $key => [$field, $vid]) {
      if (isset($item[$key])) {
        $this->setIf($node, $field, $this->terms[$vid][$item[$key]] ?? NULL);
      }
    }
    if ($node->hasField('field_jurisdiction_status')) {
      $node->set('field_jurisdiction_status', 'placeholder');
    }
    if ($node->hasField('field_service_status')) {
      $node->set('field_service_status', 'information');
    }
    if (isset($item['days_ago'])) {
      $node->setCreatedTime(\Drupal::time()->getRequestTime() - ((int) $item['days_ago'] * 86400));
    }
    $node->set('moderation_state', $item['state'] ?? 'published');
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Placeholder content (mofa:placeholder-content).');
    $node->save();
    return $node;
  }

  /**
   * Sets a field when the node has it and a value is given.
   */
  private function setIf(NodeInterface $node, string $field, mixed $value): void {
    if ($value !== NULL && $node->hasField($field)) {
      $node->set($field, $value);
    }
  }

}
