<?php

declare(strict_types=1);

namespace Drupal\mofa_core\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;

/**
 * Missions abroad: the embassy list from embassies.yml.
 *
 * The list is shown exactly as supplied by the project owner and labelled
 * unverified. Contact details stay pending until the Ministry supplies them.
 */
final class EmbassyController extends ControllerBase {

  public function __construct(
    private readonly ModuleExtensionList $moduleList,
    private readonly ThemeExtensionList $themeList,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
  }

  /**
   * Missions abroad landing page.
   */
  public function abroad(): array {
    return [
      'intro' => ['#markup' => '<p>' . $this->t('South Sudan is represented abroad by embassies and other missions.') . '</p>'],
      'tiles' => $this->component('link-tiles', [
        'heading' => '',
        'items' => [
          ['title' => (string) $this->t('Embassies'), 'url' => '/missions/abroad/embassies', 'description' => (string) $this->t('By country, with the countries each embassy also covers.')],
        ],
      ]),
    ];
  }

  /**
   * The embassy flag grid.
   */
  public function embassies(): array {
    $items = [];
    foreach ($this->load() as $slug => $entry) {
      $items[] = [
        'title' => $entry['country'],
        'description' => implode('; ', array_map(fn ($m) => $m['type'] . ' in ' . $m['city'], $entry['missions'])),
        'url' => '/missions/abroad/embassies/' . $slug,
        'flag' => $this->flag($entry['code']),
      ];
    }
    return [
      'notice' => $this->component('placeholder-notice', [
        'message' => (string) $this->t('This list was supplied for the demonstration and has not been confirmed by the Ministry. Contact details will be added once supplied.'),
      ]),
      'intro' => ['#markup' => '<p>' . $this->t('Select a country to see its embassy and the other countries it covers.') . '</p>'],
      'grid' => $this->component('flag-grid', [
        'label' => (string) $this->t('Embassies by country'),
        'items' => $items,
      ]),
    ];
  }

  /**
   * One country's embassy profile.
   */
  public function embassy(string $country): array {
    $entry = $this->load()[$country] ?? throw new NotFoundHttpException();
    return [
      'profile' => $this->component('embassy-profile', [
        'country' => $entry['country'],
        'flag' => $this->flag($entry['code']),
        'missions' => array_map(fn ($m) => ['title' => $m['type'] . ' in ' . $m['city'], 'city' => $m['city']], $entry['missions']),
        'accredited' => $entry['accredited'] ?? [],
        'back_url' => '/missions/abroad/embassies',
      ]),
    ];
  }

  /**
   * Title callback for a country's embassy profile.
   */
  public function embassyTitle(string $country): string {
    return $this->load()[$country]['country'] ?? (string) $this->t('Embassy');
  }

  /**
   * Embassies keyed by URL slug.
   */
  private function load(): array {
    static $list;
    if ($list === NULL) {
      $list = [];
      foreach (Yaml::parseFile($this->moduleList->getPath('mofa_core') . '/embassies.yml') as $entry) {
        $list[Html::getClass($entry['country'])] = $entry;
      }
    }
    return $list;
  }

  /**
   * Public path of a country flag.
   */
  private function flag(string $code): string {
    return base_path() . $this->themeList->getPath('mofa_theme') . '/images/flags/' . $code . '.svg';
  }

  /**
   * Render array for a theme component.
   */
  private function component(string $name, array $props): array {
    return [
      '#type' => 'component',
      '#component' => 'mofa_theme:' . $name,
      '#props' => $props,
    ];
  }

}
