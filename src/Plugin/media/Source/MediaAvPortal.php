<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media_avportal\MediaAvPortalResource;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a media source plugin for Media AV Portal resources.
 *
 * @MediaSource(
 *   id = "media_avportal",
 *   label = @Translation("Media AV Portal"),
 *   description = @Translation("Media AV portal."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class MediaAvPortal extends MediaSourceBase implements MediaAvPortalInterface {
  /**
   * The logger channel for media.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Media AV Portal resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface|\Drupal\media_avportal\MediaAvPortalResourceFetcher
   */
  protected $resourceFetcher;

  /**
   * The OEmbed manager service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

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
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    FieldTypePluginManagerInterface $field_type_manager,
    LoggerChannelInterface $logger,
    MessengerInterface $messenger,
    ClientInterface $http_client,
    ResourceFetcherInterface $resource_fetcher,
    UrlResolverInterface $url_resolver,
    IFrameUrlHelper $iframe_url_helper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $entity_field_manager,
      $field_type_manager,
      $config_factory
    );

    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->iFrameUrlHelper = $iframe_url_helper;
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
      $container->get('http_client'),
      $container->get('media_avportal.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper')
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
      'type' => $this->t('Resource type'),
      'title' => $this->t('Resource title'),
      'summary' => $this->t('Summary'),
      'cache_age' => $this->t('Suggested cache lifetime'),
      'default_name' => $this->t('Default name of the media item'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
      'thumbnail_width' => $this->t('Thumbnail width'),
      'thumbnail_height' => $this->t('Thumbnail height'),
      'url' => $this->t('The source URL of the resource'),
      'width' => $this->t('The width of the resource'),
      'height' => $this->t('The height of the resource'),
      'html' => $this->t('The HTML representation of the resource'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $media_ref = $this->getSourceFieldValue($media);

    try {
      $resource = $this->resourceFetcher->fetchResource($media_ref);
    }
    catch (ResourceException $e) {
      $this->messenger->addError($e->getMessage());

      return NULL;
    }

    switch ($name) {
      case 'default_name':
        $title = $this->getMetadata($media, 'title');

        if ((NULL === $title) || ('' === (string) $title)) {
          $title = $this->getMetadata($media, 'url');
        }

        return $title;

      case 'thumbnail_uri':
        return $this->getLocalThumbnailUri($resource) ?: parent::getMetadata($media, 'thumbnail_uri');

      case 'type':
        return $resource->getType();

      case 'title':
        return $resource->getTitle();

      case 'summary':
        return $resource->getSummary();

      case 'cache_age':
        return $resource->getCacheMaxAge();

      case 'thumbnail_width':
        return $resource->getThumbnailWidth();

      case 'thumbnail_height':
        return $resource->getThumbnailHeight();

      case 'url':
        $url = $resource->getUrl();
        return $url ? $url->toString() : NULL;

      case 'width':
        return $resource->getWidth();

      case 'height':
        return $resource->getHeight();

      case 'html':
        return $resource->getHtml();

      default:
        break;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'avportal_resource' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent(
      $this->getSourceFieldDefinition($type)->getName(),
      [
        'type' => 'oembed',
      ]
    );
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

    if (FALSE === file_valid_uri($thumbnails_directory)) {
      $form_state->setErrorByName(
        'thumbnails_directory',
        $this->t(
          '@path is not a valid path.',
          [
            '@path' => $thumbnails_directory,
          ]
        )
      );
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
   * {@inheritdoc}
   */
  public function getProviders() {
    $configuration = $this->getConfiguration();

    return $configuration['providers'] ?: $this->getPluginDefinition()['providers'];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    $plugin_definition = $this->getPluginDefinition();

    $label = (string) $this->t(
      '@type URL',
      [
        '@type' => $plugin_definition['label'],
      ]
    );

    return parent::createSourceField($type)->set('label', $label);
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param \Drupal\media_avportal\MediaAvPortalResource $resource
   *   The Media AV Portal resource.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   *
   * @todo Determine whether or not oEmbed media thumbnails should be stored
   * locally at all, and if so, whether that functionality should be
   * toggle-able. See https://www.drupal.org/project/drupal/issues/2962751 for
   * more information.
   */
  protected function getLocalThumbnailUri(MediaAvPortalResource $resource) {
    // If there is no remote thumbnail, there's nothing for us to fetch here.
    $remote_thumbnail_url = $resource->getThumbnailUrl();
    if (!$remote_thumbnail_url) {
      return NULL;
    }
    $remote_thumbnail_url = $remote_thumbnail_url->toString();

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

    $error_message = 'Could not download remote thumbnail from {url}.';
    $error_context = [
      'url' => $remote_thumbnail_url,
    ];
    try {
      $url = UrlHelper::parse($remote_thumbnail_url);

      $response = $this->httpClient->get($url['path']);
      if ($response->getStatusCode() === 200) {
        $success = file_unmanaged_save_data((string) $response->getBody(), $local_thumbnail_uri, FILE_EXISTS_REPLACE);

        if ($success) {
          return $local_thumbnail_uri;
        }

        $this->logger->warning($error_message, $error_context);
      }
    }
    catch (RequestException $e) {
      $this->logger->warning($e->getMessage());
    }

    return NULL;
  }

}
