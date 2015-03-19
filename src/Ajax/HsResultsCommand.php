<?php

/**
 * @file
 * Contains \Drupal\hsd8\Ajax\HsResultsCommand.
 */

namespace Drupal\hsd8\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for replacing the Hierarchical Select Output.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.hierarchicalSelectUpdate.
 */
class HsResultsCommand implements CommandInterface {

  /**
   * The Hierarchical Select results.
   *
   * @var string
   */
  protected $output;

  /**
   * Constructs a \Drupal\views\Ajax\ReplaceTitleCommand object.
   *
   * @param string $output
   *   The title of the page.
   */
  public function __construct($output) {
    $this->output = $output;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'hierarchicalSelectUpdate',
      'data' => $this->output,
    );
  }

}

