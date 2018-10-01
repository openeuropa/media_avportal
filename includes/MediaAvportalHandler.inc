<?php

/**
 * @file
 * Contains MediaInternetAvportalHandler.
 */

/**
 * Implementation of MediaInternetBaseHandler.
 *
 * @see hook_media_internet_providers().
 */
class MediaAvportalHandler extends MediaInternetBaseHandler {

  /**
   * The MediaAvportalService used by the current instance.
   *
   * @var MediaAvportalService
   */
  private $mediaAvportalService;

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
   * Check if a Avportal video id is valid.
   */
  public function validId($id) {
    try {
      $service = $this->getMediaAvportalService();

      return $service->checkMediaRefAccessibility($id);
    }
    catch (Exception $e) {
      throw new MediaInternetValidationException($e->getMessage());
    }
  }

  /**
   * Implements parent:parse().
   */
  public function parse($embedCode) {
    $listPatterns = array(
      // url: http://ec.europa.eu/avservices/video/player.cfm.
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i',
      // url: http://ec.europa.eu/avservices/play.cfm.
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i',
    );

    foreach ($listPatterns as $pattern) {
      if (preg_match($pattern, $embedCode)) {
        // Found the URL type used, let's get the query parameter from URL.
        $parsed_url = drupal_parse_url($embedCode);
        $queryParams = (!empty($parsed_url['query'])) ? $parsed_url['query'] : array();

        if (isset($queryParams['ref']) && $this->validId($queryParams['ref'])) {
          return file_stream_wrapper_uri_normalize('avportal://v/' . $queryParams['ref']);
        }
      }
    }

    // Check if the user did not insert the ref directly.
    if (preg_match('/^I[a-zA-Z0-9-]/', $embedCode)) {
      if ($this->validId($embedCode)) {
        return file_stream_wrapper_uri_normalize('avportal://v/' . $embedCode);
      }
    }
  }

  /**
   * Implements parent:claim().
   */
  public function claim($embed_code) {
    if ($this->parse($embed_code)) {
      return TRUE;
    }
  }

  /**
   * Implements parent:getFileObject().
   */
  public function getFileObject() {
    $uri = $this->parse($this->embedCode);
    $file = file_uri_to_object($uri, TRUE);

    if (empty($file->fid)) {
      $wrapper = file_stream_wrapper_get_instance_by_uri($file->uri);
      $video_title = $wrapper->getVideoTitle();
      $file->filename = truncate_utf8(strip_tags($video_title), 255);
    }
    return $file;
  }

}
