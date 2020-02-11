<?php

declare(strict_types = 1);

namespace Drupal\media_avportal_mock;

use GuzzleHttp\Middleware;

/**
 * An HTTP client middleware that wraps the Guzzle history middleware.
 *
 * Note: this service has state! It should be only used for test purposes.
 * It will keep track of the history of requests and responses across all the
 * HTTP client instances.
 */
class HistoryMiddleware {

  /**
   * Contains the HTTP client history.
   *
   * @var array
   */
  protected $historyContainer = [];

  /**
   * Wraps the history middleware for using in Drupal.
   */
  public function __invoke() {
    return Middleware::history($this->historyContainer);
  }

  /**
   * Returns the current history container.
   *
   * @return array
   *   The history container.
   */
  public function getHistoryContainer(): array {
    return $this->historyContainer;
  }

  /**
   * Returns the current history container.
   *
   * @return array|bool
   *   The last history entry.
   */
  public function getLastHistoryEntry(): ?array {
    return end($this->historyContainer);
  }

}
