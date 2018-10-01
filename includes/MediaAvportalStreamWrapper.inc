<?php

/**
 * @file
 * Create an Avportal Stream Wrapper class for the Media/Resource module.
 */

/**
 * Create an instance like this.
 *
 * @code
 *   $avportal = new MediaAvportalStreamWrapper('avportal://v/[video-code]');
 * @endcode
 */
class MediaAvportalStreamWrapper extends MediaReadOnlyStreamWrapper {

  /**
   * The MediaAvportalService used by the current instance.
   *
   * @var MediaAvportalService
   */
  private $mediaAvportalService;

  /**
   * Overrides $base_url defined in MediaReadOnlyStreamWrapper.
   *
   * @var string
   */
  protected $baseUrl = '//ec.europa.eu/avservices/play.cfm';

  /**
   * Gets a MediaAvportalService instance.
   *
   * @return MediaAvportalService
   *   The MediaAvportalService instance.
   */
  private function getMediaAvportalService() {
    if (empty($this->mediaAvportalService)) {
      $this->mediaAvportalService = new MediaAvportalService();
    }

    return $this->mediaAvportalService;
  }

  /**
   * Implements getVideoInformation().
   */
  private function getVideoInformation() {
    $parameters = $this->getUrlQueryParameter();
    $video_ref = check_plain($parameters['ref']);

    // Using cache to avoid a call to the webservice each time.
    $video_info = &drupal_static(__FUNCTION__ . $video_ref);

    if (isset($video_info)) {
      return $video_info;
    }

    $cache = cache_get('av_video_' . $video_ref);
    if (!empty($cache->data)) {
      $video_info = $cache->data;
      return $video_info;
    }
    try {
      $service = $this->getMediaAvportalService();
      $video_info = $service->searchVideoByRef($video_ref);
      cache_set('av_video_' . $video_ref, $video_info, 'cache', CACHE_TEMPORARY);

      return $video_info;
    }
    catch (Exception $e) {
      watchdog_exception('media_avportal', $e);
      throw new MediaInternetValidationException("A problem occurred while retrieving data from the service.", 0, $e);
    }
  }

  /**
   * Gets the default thumbnail from media module.
   *
   * @return bool|string
   *   The default icon is available or FALSE.
   */
  private function getDefaultThumbnail() {
    $file = file_uri_to_object($this->uri);
    $icon_dir = variable_get('media_icon_base_directory', 'public://media-icons') . '/' . variable_get('media_icon_set', 'default');
    return file_icon_path($file, $icon_dir);
  }

  /**
   * Get the URL query parameters as excpeted by the AVportal service.
   *
   * @return array
   *   Associative array where the key are the parameter names and the value,
   *   the parameter values.
   */
  protected function getUrlQueryParameter() {
    $mapped_parameters = array();
    if ($parameters = $this->get_parameters()) {
      $mapped_parameters['ref'] = $parameters['v'];

    }

    return $mapped_parameters;
  }

  /**
   * Returns a URL in the format.
   *
   * @see http://ec.europa.eu/avservices/play.cfm?ref=I-0000000
   *
   * Overrides interpolateUrl() defined in MediaReadOnlyStreamWrapper.  This is
   * an exact copy of the function in MediaReadOnlyStreamWrapper, here in case
   * that example is redefined or removed.
   */
  public function interpolateUrl() {
    if ($parameters = $this->getUrlQueryParameter()) {
      return $this->baseUrl . '?' . drupal_http_build_query($parameters);
    }
  }

  /**
   * Implements getMimeType().
   */
  public static function getMimeType($uri, $mapping = NULL) {
    return 'video/avportal';
  }

  /**
   * Implements getTarget().
   */
  public function getTarget($f) {
    return FALSE;
  }

  /**
   * Implements getVideoTitle().
   */
  public function getVideoTitle() {
    $info = $this->getVideoInformation();
    return $info->getMediaTitle();
  }

  /**
   * Implements getVideoThumb().
   */
  public function getVideoThumb() {
    $info = $this->getVideoInformation();
    return $info->getMediaThumbnailUrl();
  }

  /**
   * Gets the list of the available languages for the video.
   *
   * @return array
   *   the list of language codes.
   */
  public function getAvailableVideoLanguage() {
    $info = $this->getVideoInformation();
    return $info->getMediaAvailableLanguages();
  }

  /**
   * Implements getLocalThumbnailPath().
   */
  public function getLocalThumbnailPath() {
    $parts = $this->get_parameters();
    $ref = array_pop($parts);
    $local_path = file_default_scheme() . '://media-avportal/' . check_plain($ref) . '.jpg';
    if (file_exists($local_path)) {
      return $local_path;
    }

    try {
      $dirname = drupal_dirname($local_path);
      file_prepare_directory($dirname, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      $service = $this->getMediaAvportalService();
      $thumbnail = $service->checkMediaThumbnailFile($ref);

      if (!empty($thumbnail)) {
        file_unmanaged_save_data($thumbnail, $local_path, TRUE);
      }
      else {
        @copy($this->getVideoThumb(), $local_path);
      }
    }
    catch (Exception $e) {
      // In the event of an endpoint error, use the mime type icon provided
      // by the Media module.
      $local_path = $this->getDefaultThumbnail();
    }

    return $local_path;
  }

}
