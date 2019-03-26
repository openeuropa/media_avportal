<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Stream wrapper for the remote AV Portal photos.
 *
 * @codingStandardsIgnoreStart PSR1.Methods.CamelCapsMethodName
 */
class AvPortalPhotoWrapper implements StreamWrapperInterface {

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
  protected $config;

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
    $this->config = \Drupal::configFactory()->get('media_avportal.settings');
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
    // We hardcode the JPG path extension.
    $path = str_replace('.jpg', '', $path);
    $resource = $this->client->getResource($path);
    if (!$resource) {
      return NULL;
    }

    return $this->config->get('photos_base_uri') . $resource->getPhotoUri();
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

    $parsed = parse_url($url);
    if (!isset($parsed['scheme'])) {
      $url = 'https:' . $url;
    }

    $this->handle = ($options && STREAM_REPORT_ERRORS) ? fopen($url, $mode) : @fopen($url, $mode);

    return (bool) $this->handle;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_closedir() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_opendir($path, $options) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_readdir() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_rewinddir() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($path, $mode, $options) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($path_from, $path_to) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($path, $options) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_close() {
    return fclose($this->handle);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_eof() {
    return feof($this->handle);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_flush() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_lock($operation) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_metadata($path, $option, $value) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_read($count) {
    return fread($this->handle, $count);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_stat() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_tell() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_write($data) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($path) {
    return FALSE;
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

    // We don't have any information about the file since it is remote. So we
    // just return an array with a single value to indicate that the file is
    // actually there.
    return [
      0 => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    return FALSE;
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

}
