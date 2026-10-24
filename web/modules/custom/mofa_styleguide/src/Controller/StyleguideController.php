<?php

declare(strict_types=1);

namespace Drupal\mofa_styleguide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Development-only pages for reviewing the MoFA design system.
 *
 * Every string here is placeholder text. Nothing on these pages is official
 * Ministry information.
 */
final class StyleguideController extends ControllerBase {

  /**
   * Colour tokens, mirrored from mofa_theme/css/tokens.css for display.
   */
  private const COLORS = [
    ['--color-brand-navy', '#14284b', 'Primary brand colour: navigation, headings, primary buttons', TRUE],
    ['--color-brand-navy-dark', '#0c1a33', 'Footer background, hover states', TRUE],
    ['--color-brand-navy-tint', '#e8edf5', 'Tinted backgrounds, hover on light surfaces', TRUE],
    ['--color-ink', '#17202c', 'Headings', FALSE],
    ['--color-text', '#232d3b', 'Body text', FALSE],
    ['--color-text-muted', '#4b5666', 'Secondary text, captions', FALSE],
    ['--color-link', '#1d4a8f', 'Links', FALSE],
    ['--color-line', '#d5dbe4', 'Borders and dividers', FALSE],
    ['--color-canvas', '#f4f6f9', 'Page background', FALSE],
    ['--color-placeholder-bg', '#fff4d6', 'Placeholder notices (with #5c3c00 text)', FALSE],
    ['--color-focus', '#ffdd00', 'Keyboard focus ring (with a dark outer ring)', FALSE],
  ];

  /**
   * Layout pages: machine name => label and description.
   */
  private const LAYOUTS = [
    'home' => ['Home page', 'Identity, announcements, service pathways and featured national information.'],
    'gateway' => ['Gateway page', 'The About South Sudan pattern: short introduction, quick facts and child pages.'],
    'listing' => ['Listing page', 'News, press releases, missions or publications as a grid of cards.'],
    'detail' => ['Detail page', 'A single news item, service or profile with sources and review metadata.'],
  ];

  /**
   * The design-system overview page.
   */
  public function index(): array {
    $build = [
      '#attached' => ['library' => ['mofa_styleguide/styleguide']],
      'header' => $this->component('page-header', [
        'eyebrow' => 'Phase 1 · Development only',
        'title' => 'Design system and component library',
        'lead' => 'Tokens, components and page layouts for the proposed MoFA WEBSITE. Everything shown is placeholder content.',
      ]),
    ];

    $body = [
      '#type' => 'container',
      '#attributes' => ['class' => ['l-container', 'l-main', 'l-stack']],
    ];

    $body['notice'] = $this->component('alert', [
      'variant' => 'warning',
      'heading' => 'Temporary branding',
      'message' => 'The dark blue below follows the agreed direction but is a temporary design token, not the official MFA&IC colour. National flag colours are not used because no official digital colour values have been supplied. The coat of arms is a labelled placeholder.',
    ]);

    // Colours.
    $swatches = [];
    foreach (self::COLORS as [$token, $hex, $usage, $temporary]) {
      $ratio = $this->contrast($hex, '#ffffff');
      $swatches[] = [
        '#markup' => sprintf(
          '<li class="sg-swatch"><div class="sg-swatch__chip" style="background:%1$s"></div><div class="sg-swatch__body"><span class="sg-swatch__name">%2$s</span><code>%1$s</code><span>%3$s</span><span>Contrast with white: %4$s:1</span>%5$s</div></li>',
          $hex,
          $token,
          $usage,
          number_format($ratio, 2),
          $temporary ? '<span class="sg-temp">Temporary, not official</span>' : '',
        ),
      ];
    }
    $body['colors'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => ['sg-section']],
      'h' => ['#markup' => '<h2>Colour</h2><p class="sg-intro">Text colours meet WCAG 2.2 AA contrast (4.5:1) on white and on the page background. Contrast ratios are calculated, not estimated.</p>'],
      'list' => ['#type' => 'html_tag', '#tag' => 'ul', '#attributes' => ['class' => ['sg-swatches']], 'items' => $swatches],
    ];

    // Typography.
    $body['type'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => ['sg-section']],
      'content' => [
        '#markup' => '<h2>Typography</h2>
<p class="sg-intro">Headings use Source Serif 4; body text uses IBM Plex Sans, with IBM Plex Sans Arabic ready for the later Arabic phase. All fonts are self-hosted under the SIL Open Font License.</p>
<div class="sg-example sg-type">
  <p style="font-family:var(--font-display);font-size:var(--font-size-700);font-weight:600;line-height:1.2;margin:0">Heading level 1 sample</p>
  <p style="font-family:var(--font-display);font-size:var(--font-size-600);font-weight:600;line-height:1.2;margin:0">Heading level 2 sample</p>
  <p style="font-size:var(--font-size-400);font-weight:600;margin:0">Heading level 3 sample</p>
  <p style="font-size:var(--font-size-300);margin:0">Lead text sample. Short introductions to a page use this size.</p>
  <p style="margin:0">Body text sample. Paragraphs are limited to about 68 characters per line so they stay comfortable to read on large screens.</p>
  <p style="font-size:var(--font-size-100);margin:0;color:var(--color-text-muted)">Caption and metadata sample</p>
</div>',
      ],
    ];

    // Components.
    $examples = [
      ['Placeholder notice', 'Shown automatically on any page whose "Unverified placeholder content" box is ticked. It is ticked by default on every new item.', [
        $this->component('placeholder-notice', []),
        $this->component('placeholder-notice', ['compact' => TRUE]),
      ]],
      ['Hero', 'Opening band for the home page and gateway pages.', [
        $this->component('hero', [
          'eyebrow' => 'Placeholder eyebrow',
          'title' => 'Placeholder hero heading',
          'heading_level' => 2,
          'tagline' => 'Placeholder tagline',
          'text' => 'One or two sentences of approved introductory text will appear here.',
          'image_label' => 'Authorized national landscape photograph',
          'placeholder' => TRUE,
        ], [
          'actions' => [
            $this->component('button', ['label' => 'Primary action', 'url' => '#']),
            $this->component('button', ['label' => 'Secondary action', 'url' => '#', 'variant' => 'secondary']),
          ],
        ]),
      ]],
      ['Buttons', 'Links styled as buttons. An inactive button is used for demonstration entry points, such as diaspora registration, that must not work yet.', [
        [
          '#type' => 'container',
          '#attributes' => ['style' => 'display:flex;flex-wrap:wrap;gap:1rem;align-items:start'],
          'a' => $this->component('button', ['label' => 'Primary button', 'url' => '#']),
          'b' => $this->component('button', ['label' => 'Secondary button', 'url' => '#', 'variant' => 'secondary']),
          'c' => $this->component('button', ['label' => 'Register (demonstration)', 'inactive_note' => 'Demonstration only. Registration is not active and collects no information.']),
        ],
      ]],
      ['Cards', 'Teasers for news, missions, services and publications.', [
        [
          '#type' => 'container',
          '#attributes' => ['class' => ['l-grid']],
          'a' => $this->component('card', ['heading' => 'Placeholder news headline', 'url' => '#', 'meta' => 'Press release · 24 October 2026', 'summary' => 'Placeholder summary of one or two sentences.', 'image_label' => 'Authorized news photograph', 'placeholder' => TRUE]),
          'b' => $this->component('card', ['heading' => 'Placeholder mission name', 'url' => '#', 'meta' => 'Embassy · Placeholder city', 'summary' => 'Mission details will appear only after Ministry approval.', 'placeholder' => TRUE]),
          'c' => $this->component('card', ['heading' => 'Placeholder service title', 'url' => '#', 'meta' => 'Consular information', 'summary' => 'Information only. This does not mean the service is available online.']),
        ],
      ]],
      ['Link tiles', 'Pathways to child pages, as used by the About South Sudan gateway.', [
        $this->component('link-tiles', ['items' => [
          ['title' => 'Placeholder child page', 'url' => '#', 'description' => 'Short description of what the page covers.'],
          ['title' => 'Second child page', 'url' => '#', 'description' => 'Short description of what the page covers.'],
          ['title' => 'Third child page', 'url' => '#', 'description' => 'Short description of what the page covers.'],
        ]]),
      ]],
      ['Quick facts', 'Each fact carries its own source and status, so a pending figure cannot look approved.', [
        $this->component('quick-facts', ['heading' => 'At a glance', 'items' => [
          ['term' => 'Example approved fact', 'value' => 'Placeholder value', 'source' => 'Placeholder source', 'status' => 'approved'],
          ['term' => 'Example pending fact', 'value' => 'Placeholder value', 'source' => 'Awaiting the responsible authority', 'status' => 'pending'],
          ['term' => 'Example placeholder fact', 'value' => 'Placeholder value', 'status' => 'placeholder'],
        ]]),
      ]],
      ['Dated context notice', 'Keeps time-sensitive information apart from evergreen text, always with an as-of date.', [
        $this->component('context-notice', ['date' => '24 October 2026', 'datetime' => '2026-10-24', 'body' => 'Placeholder for a time-sensitive notice. Its owner updates or removes it when circumstances change.']),
      ]],
      ['Alerts', 'Information, warning and success messages.', [
        $this->component('alert', ['variant' => 'info', 'heading' => 'Information', 'message' => 'Placeholder information message.']),
        $this->component('alert', ['variant' => 'warning', 'heading' => 'Warning', 'message' => 'Placeholder warning message.']),
        $this->component('alert', ['variant' => 'success', 'heading' => 'Success', 'message' => 'Placeholder success message.']),
      ]],
      ['Sources and review', 'Shown at the end of reference pages so readers can see where information comes from and when it was checked.', [
        $this->component('editorial-meta', ['last_reviewed' => '24 October 2026', 'owner' => 'Placeholder department', 'sources' => [
          ['title' => 'Placeholder official source', 'url' => '#'],
          ['title' => 'Placeholder international source', 'url' => '#'],
        ]]),
      ]],
      ['Image placeholder', 'Stands in for authorized photographs, maps and illustrations. Unlicensed or unofficial images are never used instead.', [
        [
          '#type' => 'container',
          '#attributes' => ['style' => 'max-width:28rem'],
          'img' => $this->component('image-placeholder', ['label' => 'Officially approved map of South Sudan', 'ratio' => '4/3']),
        ],
      ]],
    ];

    $components = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => ['sg-section']],
      'h' => ['#markup' => '<h2>Components</h2>'],
    ];
    foreach ($examples as $i => [$title, $description, $renders]) {
      $item = [
        '#type' => 'container',
        '#attributes' => ['class' => ['sg-component']],
        'h' => ['#markup' => '<h3>' . $title . '</h3><p>' . $description . '</p>'],
      ];
      foreach ($renders as $j => $render) {
        $item['ex' . $j] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['sg-example']],
          'r' => $render,
        ];
      }
      $components['c' . $i] = $item;
    }
    $body['components'] = $components;

    // Layouts.
    $tiles = [];
    foreach (self::LAYOUTS as $id => [$label, $description]) {
      $tiles[] = [
        'title' => $label,
        'url' => Url::fromRoute('mofa_styleguide.layout', ['layout' => $id])->toString(),
        'description' => $description,
      ];
    }
    $body['layouts'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => ['sg-section']],
      'h' => ['#markup' => '<h2>Page layouts</h2><p class="sg-intro">Reusable layouts assembled from the components above. Phase 2 connects them to real Drupal content.</p>'],
      'tiles' => $this->component('link-tiles', ['heading' => '', 'items' => $tiles]),
    ];

    $build['body'] = $body;
    return $build;
  }

  /**
   * Title callback for layout pages.
   */
  public function layoutTitle(string $layout): string {
    return 'Layout: ' . self::LAYOUTS[$layout][0];
  }

  /**
   * A layout demonstration page.
   */
  public function layout(string $layout): array {
    $build = ['#attached' => ['library' => ['mofa_styleguide/styleguide']]];
    $build += match ($layout) {
      'home' => $this->homeLayout(),
      'gateway' => $this->gatewayLayout(),
      'listing' => $this->listingLayout(),
      'detail' => $this->detailLayout(),
    };
    return $build;
  }

  /**
   * Home page layout.
   */
  private function homeLayout(): array {
    $cards = [];
    foreach (['Statement', 'Press release', 'Announcement'] as $i => $type) {
      $cards['c' . $i] = $this->component('card', [
        'heading' => 'Placeholder ' . strtolower($type) . ' headline',
        'url' => '#',
        'meta' => $type . ' · Placeholder date',
        'summary' => 'Placeholder summary. Approved Ministry announcements will appear here.',
        'image_label' => $i === 0 ? 'Authorized news photograph' : NULL,
        'placeholder' => TRUE,
      ]);
    }
    return [
      'hero' => $this->component('hero', [
        'eyebrow' => 'Republic of South Sudan',
        'title' => 'Ministry of Foreign Affairs and International Cooperation',
        'tagline' => 'Placeholder welcome line',
        'text' => 'Placeholder introduction to the Ministry. Approved text will replace this.',
        'image_label' => 'Authorized national image (not a political officeholder)',
        'placeholder' => TRUE,
      ], [
        'actions' => [
          $this->component('button', ['label' => 'Services', 'url' => '#']),
          $this->component('button', ['label' => 'SSD Diaspora', 'url' => '#', 'variant' => 'secondary']),
        ],
      ]),
      'news' => $this->section('Ministry announcements', ['#type' => 'container', '#attributes' => ['class' => ['l-grid']]] + $cards, 'All news'),
      'pathways' => $this->section('Find what you need', $this->component('link-tiles', ['heading' => '', 'items' => [
        ['title' => 'Consular information', 'url' => '#', 'description' => 'Placeholder description.'],
        ['title' => 'Trade and investment', 'url' => '#', 'description' => 'Placeholder description.'],
        ['title' => 'Diaspora registration', 'url' => '#', 'description' => 'Demonstration only. Not active.'],
        ['title' => 'Find a mission', 'url' => '#', 'description' => 'Placeholder description.'],
      ]]), NULL, TRUE),
      'feature' => $this->section('Discover South Sudan', [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-with-sidebar']],
        'text' => ['#markup' => '<div class="sg-prose"><p>Placeholder feature text introducing the About South Sudan section. Approved editorial content will replace it.</p><p><a href="#">About South Sudan</a></p></div>'],
        'img' => $this->component('image-placeholder', ['label' => 'Authorized heritage landscape photograph', 'ratio' => '4/3']),
      ]),
    ];
  }

  /**
   * Gateway layout, following the approved About South Sudan concept.
   */
  private function gatewayLayout(): array {
    $children = [
      ['Basic Information', 'Official name, capital, national days and other country facts.'],
      ['History and Government', 'A neutral chronology and the system of government.'],
      ['Geography and Natural Heritage', 'Landscapes, rivers, wildlife and heritage sites.'],
      ['People and Culture', 'Communities, languages, religion and cultural life.'],
      ['Economy and Investment', 'Economic structure, with a link to trade and investment services.'],
      ['Foreign Relations', 'Foreign-policy principles and international memberships.'],
      ['National Symbols', 'The national flag, coat of arms and anthem.'],
    ];
    $items = array_map(fn ($c) => ['title' => $c[0], 'url' => '#', 'description' => $c[1]], $children);
    $facts = [];
    foreach (['Official name', 'Capital', 'Independence', 'Area', 'Official working language', 'Currency'] as $term) {
      $facts[] = ['term' => $term, 'value' => 'Placeholder, awaiting approved content', 'status' => 'placeholder'];
    }
    $facts[] = ['term' => 'Population', 'value' => 'Not published', 'source' => 'Awaiting explicit approval from the National Bureau of Statistics', 'status' => 'pending'];

    return [
      'header' => $this->component('page-header', [
        'title' => 'About South Sudan',
        'lead' => 'Discover South Sudan',
        'placeholder' => TRUE,
      ]),
      'body' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-container', 'l-main', 'l-stack']],
        'notice' => $this->component('placeholder-notice', ['message' => 'This gateway shows the approved structure only. The 500 to 800 words of introductory text, the quick facts and all images await approved content.']),
        'intro' => ['#markup' => '<div class="sg-prose"><p>Placeholder introduction. Approved, welcoming editorial text about the Republic of South Sudan will appear here, written from official government sources.</p><p>Placeholder second paragraph directing visitors to the detailed pages below.</p></div>'],
        'tiles' => $this->component('link-tiles', ['heading' => 'Explore South Sudan', 'items' => $items]),
        'facts' => $this->component('quick-facts', ['heading' => 'At a glance', 'items' => $facts]),
        'meta' => $this->component('editorial-meta', ['owner' => 'Placeholder department']),
      ],
    ];
  }

  /**
   * Listing layout.
   */
  private function listingLayout(): array {
    $grid = ['#type' => 'container', '#attributes' => ['class' => ['l-grid']]];
    $types = ['News', 'Press release', 'Statement', 'Announcement', 'News', 'Press release'];
    foreach ($types as $i => $type) {
      $grid['c' . $i] = $this->component('card', [
        'heading' => 'Placeholder headline ' . ($i + 1),
        'url' => '#',
        'meta' => $type . ' · Placeholder date',
        'summary' => 'Placeholder summary of one or two sentences.',
        'image_label' => $i % 3 === 0 ? 'Authorized news photograph' : NULL,
        'placeholder' => TRUE,
        'heading_level' => 2,
      ]);
    }
    return [
      'header' => $this->component('page-header', [
        'eyebrow' => 'News / Press',
        'title' => 'News and press releases',
        'lead' => 'Placeholder description of the news archive.',
      ]),
      'body' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-container', 'l-main']],
        'grid' => $grid,
        'pager' => ['#markup' => '<nav aria-label="Pagination"><ul class="sg-pager"><li><span aria-current="page">1</span></li><li><a href="#">2</a></li><li><a href="#">3</a></li><li><a href="#">Next</a></li></ul></nav>'],
      ],
    ];
  }

  /**
   * Detail layout.
   */
  private function detailLayout(): array {
    return [
      'header' => $this->component('page-header', [
        'eyebrow' => 'Press release',
        'title' => 'Placeholder press release headline',
        'lead' => 'Placeholder summary of one or two sentences.',
        'placeholder' => TRUE,
      ]),
      'body' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-container', 'l-main', 'l-with-sidebar']],
        'main' => [
          '#type' => 'container',
          'notice' => $this->component('placeholder-notice', []),
          'img' => $this->component('image-placeholder', ['label' => 'Authorized photograph with descriptive alternative text', 'ratio' => '16/9']),
          'text' => ['#markup' => '<div class="sg-prose" style="margin-block-start:1.5rem"><p>Placeholder paragraph. Text approved through the Ministry editorial workflow will replace it.</p><h2>Placeholder subheading</h2><p>Placeholder paragraph. Headings follow a strict order so screen-reader users can move through the page.</p></div>'],
          'meta' => $this->component('editorial-meta', ['last_reviewed' => 'Placeholder date', 'owner' => 'Placeholder department', 'sources' => [['title' => 'Placeholder source', 'url' => '#']]]),
        ],
        'aside' => ['#markup' => '<aside class="sg-related" aria-labelledby="related-heading"><h2 id="related-heading">Related</h2><ul><li><a href="#">Placeholder related link</a></li><li><a href="#">Placeholder related link</a></li></ul></aside>'],
      ],
    ];
  }

  /**
   * Wraps content in a titled page section.
   */
  private function section(string $title, array $content, ?string $more = NULL, bool $tinted = FALSE): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'section',
      '#attributes' => ['class' => array_filter(['l-section', $tinted ? 'l-section--tinted' : NULL])],
      'inner' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['l-container']],
        'head' => ['#markup' => '<div class="l-section__header"><h2>' . $title . '</h2>' . ($more ? '<a href="#">' . $more . '</a>' : '') . '</div>'],
        'content' => $content,
      ],
    ];
  }

  /**
   * Builds a render array for a theme component.
   */
  private function component(string $name, array $props, array $slots = []): array {
    return [
      '#type' => 'component',
      '#component' => 'mofa_theme:' . $name,
      '#props' => array_filter($props, fn ($v) => $v !== NULL),
      '#slots' => $slots,
    ];
  }

  /**
   * WCAG contrast ratio between two hex colours.
   */
  private function contrast(string $a, string $b): float {
    $la = $this->luminance($a);
    $lb = $this->luminance($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
  }

  /**
   * WCAG relative luminance of a hex colour.
   */
  private function luminance(string $hex): float {
    $rgb = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
    $lin = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);
    return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
  }

}
