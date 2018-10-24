<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\media\OEmbed\Resource;

/**
 * Value object representing an AVPortal resource.
 */
class MediaAvPortalResource extends Resource {
  /**
   * A text summary resource.
   *
   * @var string
   */
  protected $summary;

  /**
   * The resource Ref.
   *
   * @var string
   */
  protected $ref;

  /**
   * Get the reference.
   *
   * @return string
   *   The reference.
   */
  public function getRef(): string {
    return $this->ref;
  }

  /**
   * Set the reference.
   *
   * @param string $ref
   *   The reference.
   */
  public function setRef(string $ref): void {
    $this->ref = $ref;
  }

  /**
   * Get the summary.
   *
   * @return string
   *   The summary.
   */
  public function getSummary(): string {
    return $this->summary;
  }

  /**
   * Set the summary.
   *
   * @param string $summary
   *   The summary.
   */
  public function setSummary(string $summary): void {
    $this->summary = $summary;
  }

  /**
   * Get the iFrame src attribute.
   *
   * @param string[] $query_parts
   *   The query parts options if any.
   *
   * @return string
   *   The url.
   */
  public function getIframeSrc(array $query_parts = []) {
    // @todo How to inject this properly in this particular case ?
    $config = \Drupal::configFactory()->get('media_avportal.settings');

    $query_parts['ref'] = $this->getRef();

    // @todo: investigate those parameters.
    $query_parts += [
      'lg' => 'EN',
      'sublg' => 'none',
      'autoplay' => 'true',
      'tin' => 10,
      'tout' => 59,
    ];

    return sprintf(
      '%s?%s',
      $config->get('iframe_base_uri'),
      http_build_query($query_parts)
    );
  }

}
