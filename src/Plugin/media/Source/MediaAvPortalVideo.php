<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\AvPortalResource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a media source plugin for Media AV Portal resources.
 *
 * @MediaSource(
 *   id = "media_avportal_video",
 *   label = @Translation("Media AV Portal Video"),
 *   description = @Translation("Media AV portal Video plugin."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class MediaAvPortalVideo extends MediaSourceBase implements MediaAvPortalInterface {

  /**
   * The logger channel for media.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The AV Portal client.
   *
   * @var \Drupal\media_avportal\AvPortalClientInterface
   */
  protected $avPortalClient;

  /**
   * Constructs a new MediaAvPortal instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel for media.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media_avportal\AvPortalClientInterface $avPortalClient
   *   The AV Portal client.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerChannelInterface $logger, MessengerInterface $messenger, AvPortalClientInterface $avPortalClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->avPortalClient = $avPortalClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('logger.factory')->get('media'),
      $container->get('messenger'),
      $container->get('media_avportal.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'thumbnails_directory' => 'public://media_avportal_thumbnails',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return [
      'title' => $this->t('Resource title'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $media_ref = $this->getSourceFieldValue($media);
    $resource = $this->avPortalClient->getResource($media_ref);
    if (!$resource) {
      $this->messenger->addError($this->t('The Media resource was not found.'));
      return NULL;
    }

    switch ($name) {
      case 'default_name':
        return $this->getMetadata($media, 'title');

      case 'thumbnail_uri':
        return $this->getLocalThumbnailUri($resource) ?: parent::getMetadata($media, 'thumbnail_uri');

      case 'title':
        return $resource->getTitle();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'avportal_video_resource' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['thumbnails_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $this->configuration['thumbnails_directory'],
      '#description' => $this->t('Thumbnails will be fetched from the provider for local usage. This is the URI of the directory where they will be placed.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $thumbnails_directory = $form_state->getValue('thumbnails_directory');
    if (!file_valid_uri($thumbnails_directory)) {
      $form_state->setErrorByName('thumbnails_directory', $this->t('@path is not a valid path.', [
        '@path' => $thumbnails_directory,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    parent::prepareFormDisplay($type, $display);
    $source_field = $this->getSourceFieldDefinition($type)->getName();
    $display->setComponent($source_field, [
      'type' => 'avportal_textfield',
      'weight' => $display->getComponent($source_field)['weight'],
    ]);
    $display->removeComponent('name');
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param \Drupal\media_avportal\AvPortalResource $resource
   *   The resource object.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(AvPortalResource $resource) {
    // If there is no resource, there's nothing for us to fetch here.
    $remote_thumbnail_url = $resource->getThumbnailUrl();
    if (!$remote_thumbnail_url) {
      return NULL;
    }

    // Compute the local thumbnail URI, regardless of whether or not it exists.
    $configuration = $this->getConfiguration();
    $directory = $configuration['thumbnails_directory'];
    $local_thumbnail_uri = $directory . '/' . Crypt::hashBase64($remote_thumbnail_url) . '.' . pathinfo($remote_thumbnail_url, PATHINFO_EXTENSION);

    // If the local thumbnail already exists, return its URI.
    if (file_exists($local_thumbnail_uri)) {
      return $local_thumbnail_uri;
    }

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    $thumbnail = $this->avPortalClient->getVideoThumbnail($resource);
    if (!$thumbnail) {
      $error_message = 'Could not download remote thumbnail from {url}.';
      $error_context = [
        'url' => $remote_thumbnail_url,
      ];
      $this->logger->warning($error_message, $error_context);
      return NULL;
    }

    $success = file_unmanaged_save_data((string) $thumbnail, $local_thumbnail_uri, FILE_EXISTS_REPLACE);

    if ($success) {
      return $local_thumbnail_uri;
    }

    return NULL;
  }

}
