<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\AvPortalResource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'avportal_photo' formatter.
 *
 * @FieldFormatter(
 *   id = "avportal_photo",
 *   label = @Translation("AV Portal Photo"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class AvPortalPhotoFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The AV Portal client.
   *
   * @var \Drupal\media_avportal\AvPortalClientInterface
   */
  protected $avPortalClient;

  /**
   * The AV Portal settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AvPortalPhotoFormatter instance.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\media_avportal\AvPortalClientInterface $avportal_client
   *   The AV Portal client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, AvPortalClientInterface $avportal_client, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->logger = $logger_factory->get('media');
    $this->avPortalClient = $avportal_client;
    $this->config = $config_factory->get('media_avportal.settings');
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('logger.factory'),
      $container->get('media_avportal.client'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);

    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if (!parent::isApplicable($field_definition)) {
      return FALSE;
    }

    $media_type_id = $field_definition->getTargetBundle();
    if (!$media_type_id) {
      // We need to allow to use this formatter also in cases where the field is
      // not bundle-specific, like when it's a base field or it's used as a
      // formatter for Views. So we rely on developers not using this formatter
      // on fields where it doesn't make any sense.
      return TRUE;
    }

    $media_type = MediaType::load($media_type_id);
    return $media_type && $media_type->getSource() instanceof MediaAvPortalPhotoSource;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item);
    }

    $cache = new CacheableMetadata();
    $image_style = $this->settings['image_style'] ?? NULL;
    if (!empty($image_style)) {
      $image_style = $this->entityTypeManager->getStorage('image_style')->load($image_style);
      if ($image_style instanceof ImageStyleInterface) {
        $cache->addCacheableDependency($image_style);
      }
    }

    $cache->applyTo($elements);

    return $elements;
  }

  /**
   * Renders a single field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The individual field item.
   *
   * @return array
   *   The Drupal element.
   */
  protected function viewElement(FieldItemInterface $item): array {
    $main_property = $item->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();

    $resource_ref = $item->{$main_property};

    if (empty($resource_ref)) {
      return [];
    }

    $resource = $this->avPortalClient->getResource($resource_ref);

    if (!$resource instanceof AvPortalResource) {
      $this->logger->error('Could not retrieve the remote reference (@ref).', ['@ref' => $resource_ref]);
      return [];
    }

    $image_style = $this->settings['image_style'] ?? NULL;
    $theme = 'image';
    if (!empty($image_style)) {
      $image_style = $this->entityTypeManager->getStorage('image_style')->load($image_style);
      if ($image_style instanceof ImageStyleInterface) {
        $theme = 'image_style';
      }
    }

    // Get the media source from the entity.
    $media = $item->getEntity();
    $media_source = $media->getSource();

    $build = [
      '#theme' => $theme,
      '#attributes' => [
        'class' => ['avportal-photo'],
        'alt' => $media_source->getMetadata($media, 'thumbnail_alt_value'),
      ],
      // We need to append an image prefix to the stream URI.
      // @see \Drupal\media_avportal\StreamWrapper\AvPortalPhotoStreamWrapper::getExternalUrl().
      '#uri' => 'avportal://' . $resource_ref . '.jpg',
    ];

    if ($theme === 'image_style') {
      $build['#style_name'] = $image_style->id();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    // The image styles are a dependency of this formatter.
    // @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter.
    $style_id = $this->getSetting('image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = $this->entityTypeManager->getStorage('image_style')->load($style_id)) {
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    // Ensure that the image style gets changed if the configured one is removed
    // and a replacement is specified.
    // @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter.
    $style_id = $this->getSetting('image_style');
    /** @var \Drupal\image\ImageStyleStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = $storage->load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $storage->getReplacementId($style_id);
        if ($replacement_id && $storage->load($replacement_id)) {
          $this->setSetting('image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

}
