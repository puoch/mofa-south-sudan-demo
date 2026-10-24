<?php

declare(strict_types=1);

namespace Drupal\mofa_core\Drush\Commands;

use Drupal\mofa_core\NavigationBuilder;
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

}
