<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_avportal\AvPortalPullMedia;
use Drush\Commands\DrushCommands;

/**
 * Class against drush commands for avportal medias.
 */
class AvPortalCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The AV Portal pull media service.
   *
   * @var \Drupal\media_avportal\AvPortalPullMedia
   */
  protected $avPortalPullMedia;

  /**
   * Media AV Portal commands constructor.
   *
   * @param \Drupal\media_avportal\AvPortalPullMedia $avportal_pull_media
   *   The service that update existing media.
   */
  public function __construct(AvPortalPullMedia $avportal_pull_media) {
    $this->avPortalPullMedia = $avportal_pull_media;
  }

  /**
   * Update existing AvPortal Media entities with data from remote service.
   *
   * @param array $options
   *   Command options.
   *
   * @command media-avportal:pull-avportal-medias
   * @option mids A comma-separated list of media ids to update. If omitted, all av portal medias will be updated.
   * @validate-module-enabled media_avportal
   */
  public function runPullAvportalMedias(array $options = ['mids' => self::OPT]): void {
    $mids = $options['mids'] ? $options['mids'] : [];

    // Build operations for updating avportal medias with remote data.
    $operations = [];
    foreach ($mids as $mid) {
      $operations[] = [
        [$this->avPortalPullMedia, 'pullAvPortalMedia'],
        [$mid],
      ];
    }

    $operations = $operations ? $operations : [[
        [$this->avPortalPullMedia, 'pullAvPortalMedia'],
        [NULL],
    ],
    ];

    $batch = [
      'operations' => $operations,
      'title' => $this->t('Update AVPortal medias.'),
      'progress_message' => '',
      'error_message' => $this->t('Error on updating avportal medias'),
    ];

    batch_set($batch);

    drush_backend_batch_process();
  }

}
