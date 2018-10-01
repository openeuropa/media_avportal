<?php

/**
 * @file
 * Definition of MediaAvportalService.
 */

/**
 * Class MediaAvportalService.
 *
 * It is in charge to send the requests to the AV portal service.
 */
class MediaAvportalService {
  /**
   * The URL to connect to the service.
   *
   * @var string
   */
  private $serviceUrl = 'https://ec.europa.eu/tools/b2services/proxy/avssearch/avsportal/avshandler';

  /**
   * MediaAvportalService constructor.
   *
   * @param array $connectionParameters
   *   (Optional) The connection parameters.
   *   It must contains at least the service URL.
   *
   * @throws MediaAvPortalServiceException
   *   Catched if the connection parameters are not set correctly.
   */
  public function __construct(array $connectionParameters = array()) {
    // No other parameters than the service URL but let's prepare the future.
  }

  /**
   * Send a request to the service.
   *
   * @param array $queryParameters
   *   Associative array containing the query parameters,
   *   The keys are the query parameter name.
   * @param array $connectionParameters
   *   (Optional) An array that can have one or more of the following elements:
   *   - headers: An array containing request headers to send as name/value
   *     pairs.
   *   - method: A string containing the request method. Defaults to 'GET'.
   *     Defaults to NULL.
   *   - max_redirects: An integer representing how many times a redirect
   *     may be followed. Defaults to 3.
   *   - timeout: A float representing the maximum number of seconds the
   *     function call may take. The default is 30 seconds. If a timeout
   *     occurs, the error code is set to the HTTP_REQUEST_TIMEOUT constant.
   *   - context: A context resource created with stream_context_create().
   *
   * @return object
   *   An object that can have one or more of the following components:
   *   - request: A string containing the request body that was sent.
   *   - code: An integer containing the response status code, or the error
   *     code if an error occurred.
   *   - protocol: The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
   *   - status_message: The status message from the response, if a response
   *     was received.
   *   - redirect_code: If redirected, an integer containing the initial
   *     response status code.
   *   - redirect_url: If redirected, a string containing the URL of the
   *     redirect target.
   *   - error: If an error occurred, the error message. Otherwise not set.
   *   - headers: An array containing the response headers as name/value pairs.
   *     HTTP header names are case-insensitive (RFC 2616, section 4.2), so for
   *     easy access the array keys are returned in lower case.
   *   - data: A string containing the response body that was received.
   *
   * @throws MediaAvPortalServiceException
   *   Catch if the request to the service failed.
   */
  private function sendServiceRequest(array $queryParameters, array $connectionParameters = array()) {
    // Ensure that the response is always JSON formatted.
    $queryParameters['wt'] = 'json';
    $url = $this->serviceUrl . '?' . drupal_http_build_query($queryParameters);

    try {
      return drupal_http_request($url, $connectionParameters);
    }
    catch (Exception $e) {
      watchdog_exception('media_avportal', $e);
      $message = sprintf('The query execution failed on %s', $this->serviceUrl);
      throw new MediaAvPortalServiceException($message);
    }
  }

  /**
   * Normalize a media reference to be recognized by the AV Portal service.
   *
   * @param string $refToNormalize
   *   The reference value to normalize.
   *
   * @return string
   *   The normalized reference value.
   */
  protected function normalizeRef($refToNormalize) {
    if (stripos($refToNormalize, 'I-') === 0) {
      return trim($refToNormalize);
    }

    $refToNormalize = (string) preg_replace('/^I|^i/', 'I-', $refToNormalize);

    return trim($refToNormalize);
  }

  /**
   * Gets the metadata for the specified reference.
   *
   * @param string $ref
   *   The reference, id of the video, its format is either "I-000000" or
   *   "I000000".
   *
   * @return MediaAvPortalMedia|bool
   *   The MediaAvPortalMedia object with the video metadata or
   *   FALSE if not found.
   */
  public function searchVideoByRef($ref) {
    $ref = $this->normalizeRef($ref);
    $searchParameters = array(
      'ref' => $ref,
      'type' => 'VIDEO',
    );
    $results = $this->searchVideos($searchParameters);

    if (empty($results)) {
      return FALSE;
    }

    return reset($results);
  }

  /**
   * Executes a video search via the AVportal services.
   *
   * @param array $searchParameters
   *   Associative array defining the search parameters:
   *   - The key is the parameter name,
   *   - The value the parameter value.
   * @param string $language
   *   The reference language to use for the metadata of each retrieved video.
   *
   * @return array
   *   An array of MediaAvPortalMedia objects corresponding to the search
   *   criteria.
   *
   * @throws MediaAvPortalMalformedQueryException
   *   Catch if the search parameters are wrongly set.
   * @throws MediaAvportalMediaNotAccessibleException
   *   Catch if the search request to the service returns no video information.
   * @throws MediaAvPortalServiceException
   *   Catch if the search request to the service failed.
   */
  public function searchVideos(array $searchParameters = array(), $language = 'en') {
    if (empty($searchParameters['ref']) && empty($searchParameters['kwand'])  && empty($searchParameters['themes'])) {
      throw new MediaAvPortalMalformedQueryException('One of these query parameters is mandatory: ref, kwand, themes.');
    }

    $query = array(
      'fl' => 'ref,titles_json,summary_json,type,media_json,mediaorder_json,languages',
      'pagesize' => 15,
    );
    $query = array_merge($query, $searchParameters);

    // Force some configuration to ensure that only published video are
    // retrieved and the data are in JSON.
    $query['type'] = 'VIDEO';
    $query['hasMedia'] = 1;
    $query['wt'] = 'json';

    $results = array();
    $response = $this->sendServiceRequest($query);
    if (isset($response->error)) {
      $message = sprintf('The query execution failed on %s: %s', $this->serviceUrl, $response->error);
      throw new MediaAvPortalServiceException($message);
    }

    $decoded_json_data = drupal_json_decode($response->data);

    if (empty($decoded_json_data['response']) || empty($decoded_json_data['response']['docs'])) {
      throw new MediaAvportalMediaNotAccessibleException('No information about the video has been found.');
    }

    foreach ($decoded_json_data['response']['docs'] as $media_array) {
      $results[] = new MediaAvPortalMedia($media_array, $language);
    }

    return $results;
  }

  /**
   * Check if the specified media is accessible.
   *
   * @param string $ref
   *   The media reference to search.
   *
   * @return bool
   *   TRUE, if the media is available.
   *
   * @throws MediaAvportalMediaNotAccessibleException
   *   Catch if the media is not found or accessible.
   */
  public function checkMediaRefAccessibility($ref) {
    $ref = $this->normalizeRef($ref);

    $query = array(
      'fl' => 'ref',
      'ref' => $ref,
      'type' => 'VIDEO',
      'hasMedia' => 1,
    );
    $response = $this->sendServiceRequest($query);
    $decoded_json_data = drupal_json_decode($response->data);

    if ($response->code == 401 || empty($decoded_json_data['response']) || empty($decoded_json_data['response']['docs'])) {
      throw new MediaAvportalMediaNotAccessibleException('Embedding has been disabled for this avportal video.');
    }

    if ($response->code != 200) {
      throw new MediaAvportalMediaNotAccessibleException('The avportal video ID is invalid or the video has been deleted.');
    }

    return TRUE;
  }

  /**
   * Gets the media thumbnail file.
   *
   * @param string $ref
   *   The media reference.
   * @param string $language
   *   (Optional) The language to use to get the file.
   *   If not set, the media default language will be used.
   *
   * @return bool|string
   *   The checked thumbnail file URL or FALSE if the file url is
   *   invalid or the file is not found.
   */
  public function checkMediaThumbnailFile($ref, $language = '') {
    $media = $this->searchVideoByRef($ref);
    $url = $media->getMediaDefaultThumbnailUrl();
    $availableTranslatedUrls = $media->getMediaTranslatedThumbnailUrls();
    if (!empty($language) && isset($availableTranslatedUrls[$language])) {
      $url = $availableTranslatedUrls[$language];
    }

    try {
      $response = drupal_http_request($url);

      if (!isset($response->error)) {
        return $response->data;
      }
    }
    catch (Exception $e) {
      // Let's log the exception leave the function process continue with
      // its own "error" management.
      watchdog_exception('media_avportal', $e);
    }

    watchdog('media_avportal', 'The url "%url" is invalid or does not exist.', array('%url' => $url), WATCHDOG_ERROR);

    return FALSE;
  }

}
