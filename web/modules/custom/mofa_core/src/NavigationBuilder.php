<?php

declare(strict_types=1);

namespace Drupal\mofa_core;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Creates or updates menu links from navigation.yml.
 *
 * Menu links are content in Drupal, so they are not part of config export.
 * This keeps the proposed information architecture in version control and
 * makes it reproducible on any environment. Running it again updates
 * existing links (matched by menu, parent and path) instead of duplicating.
 */
final class NavigationBuilder {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $moduleList,
  ) {}

  /**
   * Builds all menus. Returns the number of links created or updated.
   */
  public function build(): int {
    $file = $this->moduleList->getPath('mofa_core') . '/navigation.yml';
    $menus = Yaml::decode(file_get_contents($file));
    $count = 0;
    foreach ($menus as $menu => $items) {
      $count += $this->buildLevel($menu, $items, '');
    }
    return $count;
  }

  /**
   * Builds one level of a menu.
   */
  private function buildLevel(string $menu, array $items, string $parent): int {
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $count = 0;
    foreach (array_values($items) as $weight => $item) {
      $uri = $item['path'] === '/' ? 'internal:/' : 'internal:' . $item['path'];
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('menu_name', $menu)
        ->condition('link.uri', $uri);
      $parent ? $query->condition('parent', $parent) : $query->notExists('parent');
      $ids = $query->execute();
      $link = $ids ? $storage->load(reset($ids)) : $storage->create(['menu_name' => $menu, 'link' => ['uri' => $uri]]);
      $link->set('title', $item['title']);
      $link->set('parent', $parent ?: NULL);
      $link->set('weight', $weight);
      $link->set('expanded', !empty($item['children']));
      $link->set('enabled', TRUE);
      $link->save();
      $count++;
      if (!empty($item['children'])) {
        $count += $this->buildLevel($menu, $item['children'], $link->getPluginId());
      }
    }
    return $count;
  }

}
