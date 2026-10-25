<?php

declare(strict_types=1);

namespace Drupal\mofa_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The home page, assembled from site content.
 *
 * - Hero tagline: the site slogan (Configuration > Basic site settings).
 * - Announcements: the three latest published news items.
 * - Discover South Sudan: the summary and opening text of the About South
 *   Sudan gateway page.
 */
final class HomeController extends ControllerBase {

  public function __construct(
    private readonly AliasManagerInterface $aliasManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('path_alias.manager'));
  }

  /**
   * Builds the home page.
   */
  public function build(): array {
    $site = $this->config('system.site');
    $slogan = trim((string) $site->get('slogan'));

    $build = [
      '#cache' => ['tags' => ['node_list', 'config:system.site']],
      'hero' => $this->component('hero', [
        'eyebrow' => (string) $this->t('Republic of South Sudan'),
        'title' => (string) $this->t('Ministry of Foreign Affairs and International Cooperation'),
        'tagline' => $slogan !== '' ? $slogan : (string) $this->t('Placeholder welcome line'),
        'text' => (string) $this->t('Placeholder introduction to the Ministry. Approved text will replace it.'),
        'image_label' => (string) $this->t('Authorized national image (not a political officeholder)'),
        'placeholder' => TRUE,
      ], [
        'actions' => [
          $this->component('button', ['label' => (string) $this->t('Services'), 'url' => '/services']),
          $this->component('button', ['label' => (string) $this->t('SSD Diaspora'), 'url' => '/diaspora', 'variant' => 'secondary']),
        ],
      ]),
    ];

    // Portrait supplied by the project owner (25 October 2026). Only the role
    // they gave is shown; the message stays a labelled placeholder.
    $theme_path = base_path() . \Drupal::service('extension.path.resolver')->getPath('theme', 'mofa_theme');
    $build['leader'] = $this->section((string) $this->t('Message from the Minister'), $this->component('leader-message', [
      'role' => (string) $this->t('Caretaker Minister of Foreign Affairs and International Cooperation, Republic of South Sudan'),
      'text' => (string) $this->t('Placeholder welcome message. The Minister\'s approved message will replace it.'),
      'image_src' => $theme_path . '/images/minister-portrait',
      'image_alt' => (string) $this->t('Portrait of the caretaker Minister of Foreign Affairs and International Cooperation'),
      'placeholder' => TRUE,
    ]));

    $build['news'] = $this->section(
      (string) $this->t('Ministry announcements'),
      views_embed_view('news', 'latest') ?? [],
      ['label' => (string) $this->t('All news and press'), 'url' => '/news'],
    );

    $build['pathways'] = $this->section((string) $this->t('Find what you need'), $this->component('link-tiles', [
      'heading' => '',
      'items' => [
        ['title' => (string) $this->t('Consular information'), 'url' => '/services/consular', 'description' => (string) $this->t('Information only.')],
        ['title' => (string) $this->t('Trade and investment'), 'url' => '/services/trade-and-investment', 'description' => (string) $this->t('Information for investors and businesses.')],
        ['title' => (string) $this->t('Diaspora registration'), 'url' => '/diaspora/registration', 'description' => (string) $this->t('Demonstration only. Not active.')],
        ['title' => (string) $this->t('Find a mission'), 'url' => '/missions/directory', 'description' => (string) $this->t('Embassies and other representations.')],
      ],
    ]), NULL, TRUE);

    $gateway = $this->nodeByAlias('/about-south-sudan');
    if ($gateway) {
      $intro = '';
      if (!$gateway->get('body')->isEmpty()) {
        preg_match('#<p>(.*?)</p>#s', $gateway->get('body')->value, $m);
        $intro = trim(strip_tags($m[1] ?? ''));
      }
      $build['discover'] = $this->section((string) $this->t('Discover South Sudan'), [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-with-sidebar', 'home-discover']],
        'text' => [
          '#type' => 'container',
          'p' => ['#markup' => '<p>' . htmlspecialchars($intro) . '</p>'],
          'link' => $this->component('button', [
            'label' => (string) $this->t('About South Sudan'),
            'url' => $gateway->toUrl()->toString(),
            'variant' => 'secondary',
          ]),
        ],
        'image' => $this->component('image-placeholder', [
          'label' => (string) $this->t('Authorized heritage landscape photograph'),
          'ratio' => '4/3',
        ]),
      ]);
      $build['#cache']['tags'] = array_merge($build['#cache']['tags'], $gateway->getCacheTags());
    }

    return $build;
  }

  /**
   * Loads a node by its path alias.
   */
  private function nodeByAlias(string $alias): ?NodeInterface {
    $path = $this->aliasManager->getPathByAlias($alias);
    if (preg_match('#^/node/(\d+)$#', $path, $m)) {
      $node = $this->entityTypeManager()->getStorage('node')->load($m[1]);
      return $node instanceof NodeInterface && $node->access('view') ? $node : NULL;
    }
    return NULL;
  }

  /**
   * Wraps content in a titled section.
   */
  private function section(string $title, array $content, ?array $more = NULL, bool $tinted = FALSE): array {
    $head = '<div class="l-section__header"><h2>' . htmlspecialchars($title) . '</h2>';
    if ($more) {
      $head .= '<a href="' . htmlspecialchars($more['url']) . '">' . htmlspecialchars($more['label']) . '</a>';
    }
    $head .= '</div>';
    return [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => array_values(array_filter(['l-section', $tinted ? 'l-section--tinted' : NULL]))],
      'inner' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-container']],
        'head' => ['#markup' => $head],
        'content' => $content,
      ],
    ];
  }

  /**
   * Render array for a theme component.
   */
  private function component(string $name, array $props, array $slots = []): array {
    return [
      '#type' => 'component',
      '#component' => 'mofa_theme:' . $name,
      '#props' => $props,
      '#slots' => $slots,
    ];
  }

}
