<?php

declare(strict_types=1);

namespace Drupal\mofa_core\Drush\Commands;

use Drupal\mofa_core\NavigationBuilder;
use Drupal\mofa_core\PlaceholderContentBuilder;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush commands for the MoFA WEBSITE.
 */
final class MofaCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    #[Autowire(service: 'mofa_core.navigation_builder')]
    private readonly NavigationBuilder $navigationBuilder,
    #[Autowire(service: 'mofa_core.placeholder_content_builder')]
    private readonly PlaceholderContentBuilder $placeholderContentBuilder,
  ) {
    parent::__construct();
  }

  /**
   * Create or update the main and footer navigation from navigation.yml.
   */
  #[CLI\Command(name: 'mofa:navigation')]
  #[CLI\Usage(name: 'drush mofa:navigation', description: 'Build the proposed navigation.')]
  public function navigation(): void {
    $count = $this->navigationBuilder->build();
    $this->logger()->success(sprintf('Navigation built: %d links created or updated.', $count));
  }

  /**
   * Create or update the Phase 2 placeholder content from placeholder_content.yml.
   *
   * Every item is synthetic and marked as unverified placeholder content.
   */
  #[CLI\Command(name: 'mofa:placeholder-content')]
  #[CLI\Usage(name: 'drush mofa:placeholder-content', description: 'Build the placeholder pages, terms and listings content.')]
  public function placeholderContent(): void {
    [$terms, $nodes] = $this->placeholderContentBuilder->build();
    $this->logger()->success(sprintf('Placeholder content built: %d terms and %d nodes created or updated.', $terms, $nodes));
  }

}
