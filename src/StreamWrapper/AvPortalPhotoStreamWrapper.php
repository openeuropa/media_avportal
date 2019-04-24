<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\remote_stream_wrapper\StreamWrapper\HttpStreamWrapper;

/**
 * Stream wrapper for the remote AV Portal photos.
 *
 * @codingStandardsIgnoreStart PSR1.Methods.CamelCapsMethodName
 */
class AvPortalPhotoStreamWrapper extends HttpStreamWrapper {

  use StringTranslationTrait;

  /**
   * The Stream URI.
   *
   * @var string
   */
  protected $uri;

  /**
   * The AV Portal client.
   *
   * @var \Drupal\media_avportal\AvPortalClientInterface
   */
  protected $client;

  /**
   * The AV Portal configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configuration;

  /**
   * The resource handle.
   *
   * @var resource
   */
  protected $handle;

  /**
   * AvPortalPhotoWrapper constructor.
   */
  public function __construct() {
    // Dependency injection does not work with stream wrappers.
    $this->client = \Drupal::service('media_avportal.client');
    $this->configuration = \Drupal::configFactory()->get('media_avportal.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('AV Portal photo stream wrapper');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Stream wrapper for the remote location where AV Portal photos can be found.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::READ;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    // The stream wrapper URI expects an image extension because otherwise it
    // cannot be used for generating image styles. When the latter happens, the
    // extension is checked to determine whether the file is supported by the
    // available image toolkit. We default to (assume) JPG as the resources
    // are photos.
    // @see \Drupal\image\Entity\ImageStyle::supportsUri().
    $path = str_replace('.jpg', '', $path);
    $resource = $this->client->getResource($path);
    if (!$resource) {
      return NULL;
    }

    return $this->configuration->get('photos_base_uri') . $resource->getPhotoUri();
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    return $this->getTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_open($path, $mode, $options, &$opened_path) {
    $allowed_modes = ['r', 'rb'];
    if (!in_array($mode, $allowed_modes)) {
      return FALSE;
    }

    $this->uri = $path;
    $url = $this->getExternalUrl();
    if (!$url) {
      return FALSE;
    }

    $url = $this->getFullExternalUrl($url);

    return parent::stream_open($url, $mode, $options, $opened_path);
  }

  /**
   * {@inheritdoc}
   */
  public function url_stat($path, $flags) {
    $this->uri = $path;

    $path = str_replace('\\', '/', $this->getTarget());
    $path = str_replace('.jpg', '', $path);
    $resource = $this->client->getResource($path);
    if (!$resource) {
      return FALSE;
    }

    $url = $this->configuration->get('photos_base_uri') . $resource->getPhotoUri();
    $url = $this->getFullExternalUrl($url);

    return parent::url_stat($url, $flags);
  }

  /**
   * Appends the scheme to the external URL retrieved by getExternalUrl().
   *
   * @param string $url
   *   The external URL.
   *
   * @return string
   *   The full external URL.
   */
  protected function getFullExternalUrl(string $url): string {
    $parsed = parse_url($url);
    if (!isset($parsed['scheme'])) {
      $url = 'https:' . $url;
    }

    return $url;
  }

  /**
   * Returns the local target of the resource within the stream.
   *
   * @param null $uri
   *   The URI.
   *
   * @return string
   *   The target.
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);

    return trim($target, '\/');
  }

  /**
   * {@inheritdoc}
   */
  public function preventUnmanagedFileImageStyleGeneration() {
    return FALSE;
  }

}
