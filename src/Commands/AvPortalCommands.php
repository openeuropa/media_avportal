<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_avportal\AvPortalMediaUpdater;
use Drush\Commands\DrushCommands;

/**
 * Drush integration for the Media AV Portal module.
 */
class AvPortalCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The AV Portal pull media service.
   *
   * @var \Drupal\media_avportal\AvPortalMediaUpdater
   */
  protected $avPortalMediaUpdater;

  /**
   * Media AV Portal commands constructor.
   *
   * @param \Drupal\media_avportal\AvPortalMediaUpdater $avPortalMediaUpdater
   *   The service that update existing media.
   */
  public function __construct(AvPortalMediaUpdater $avPortalMediaUpdater) {
    parent::__construct();

    $this->avPortalMediaUpdater = $avPortalMediaUpdater;
  }

  /**
   * Refresh from the source all the mapped fields of AV Portal medias.
   *
   * @param array $options
   *   Command options.
   *
   * @command media-avportal:refresh-mapped-fields
   * @option mids A comma-separated list of media ids to update. If omitted, all av portal medias will be updated.
   * @validate-module-enabled media_avportal
   */
  public function runRefreshMappedFields(array $options = ['mids' => self::OPT]): void {
    $mids = $options['mids'] ? $options['mids'] : [];

    // Build operations for updating avportal medias with remote data.
    $operations = [];
    foreach ($mids as $mid) {
      $operations[] = [
        [$this->avPortalMediaUpdater, 'refreshMappedFields'],
        [$mid],
      ];
    }

    $operations = $operations ? $operations : [
      [
        [$this->avPortalMediaUpdater, 'refreshMappedFields'],
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
