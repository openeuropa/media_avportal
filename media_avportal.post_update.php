<?php

/**
 * @file
 * Post updates functions for Media AV Portal.
 */

declare(strict_types = 1);

/**
 * Sets caching of AV Portal responses to one hour.
 */
function media_avportal_post_update_enable_response_cache(): void {
  $config = \Drupal::configFactory()->getEditable('media_avportal.settings');
  $config->set('cache_max_age', 3600);
  $config->save(TRUE);
}
