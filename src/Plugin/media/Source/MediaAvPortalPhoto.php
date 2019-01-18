<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

/**
 * Provides a media source plugin for Media AV Portal resources.
 *
 * @MediaSource(
 *   id = "media_avportal_photo",
 *   label = @Translation("Media AV Portal Photo"),
 *   description = @Translation("Media AV portal photo plugin."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class MediaAvPortalPhoto extends MediaAvPortalBaseSource {

}
