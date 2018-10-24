<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media_avportal\MediaAvPortalResource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'avportal' formatter.
 *
 * @FieldFormatter(
 *   id = "avportal",
 *   label = @Translation("AV Portal iframe"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long"
 *   },
 * )
 */
class AVPortalFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The default width.
   */
  public const DEFAULT_WIDTH = 640;

  /**
   * The default height.
   */
  public const DEFAULT_HEIGHT = 390;

  /**
   * The resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The media settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Constructs an AVPortalFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->messenger = $messenger;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->logger = $logger_factory->get('media');
    $this->config = $config_factory->get('media.settings');
    $this->iFrameUrlHelper = $iframe_url_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media_avportal.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_width' => 0,
      'max_height' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $max_width = $this->getSetting('max_width');
    $max_height = $this->getSetting('max_height');

    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getMainPropertyName();
      $resource_ref = $item->{$main_property};

      if (empty($resource_ref)) {
        continue;
      }

      try {
        /** @var \Drupal\media_avportal\MediaAvPortalResource $resource */
        $resource = $this->resourceFetcher->fetchResource($resource_ref);
      }
      catch (ResourceException $exception) {
        $this->logger->error('Could not retrieve the remote reference (@ref).', ['@ref' => $resource_ref]);
        continue;
      }

      switch ($resource->getType()) {
        case MediaAvPortalResource::TYPE_VIDEO:
          $element[$delta] = [
            '#type' => 'html_tag',
            '#tag' => 'iframe',
            '#attributes' => [
              'src' => $resource->getIframeSrc(),
              'frameborder' => 0,
              'scrolling' => FALSE,
              'allowtransparency' => TRUE,
              'width' => $max_width ?: self::DEFAULT_WIDTH,
              'height' => $max_height ?: self::DEFAULT_HEIGHT,
            ],
          ];
          break;

        default:
          throw new ResourceException('Unknown resource type: ' . $resource->getType(), $resource->getIframeSrc(), $resource->getRef());
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'max_width' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum width'),
        '#default_value' => $this->getSetting('max_width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
      ],
      'max_height' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum height'),
        '#default_value' => $this->getSetting('max_height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('max_width') && $this->getSetting('max_height')) {
      $summary[] = $this->t('Maximum size: %max_width x %max_height pixels', [
        '%max_width' => $this->getSetting('max_width'),
        '%max_height' => $this->getSetting('max_height'),
      ]);
    }
    elseif ($this->getSetting('max_width')) {
      $summary[] = $this->t('Maximum width: %max_width pixels', [
        '%max_width' => $this->getSetting('max_width'),
      ]);
    }
    elseif ($this->getSetting('max_height')) {
      $summary[] = $this->t('Maximum height: %max_height pixels', [
        '%max_height' => $this->getSetting('max_height'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ('media' !== $field_definition->getTargetEntityTypeId()) {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $media_type = $field_definition->getTargetBundle();

      if (NULL !== $media_type) {
        $media_type = MediaType::load($media_type);

        return $media_type && $media_type->getSource() instanceof MediaAvPortalInterface;
      }
    }

    return FALSE;
  }

}
