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
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\AvPortalResource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'avportal_photo_responsive' formatter.
 *
 * @FieldFormatter(
 *   id = "avportal_photo_responsive",
 *   label = @Translation("AV Portal Photo Responsive"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class AvPortalPhotoResponsiveFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs an AvPortalPhotoResponsiveFormatter instance.
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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, AvPortalClientInterface $avportal_client, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LinkGeneratorInterface $link_generator, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->logger = $logger_factory->get('media');
    $this->avPortalClient = $avportal_client;
    $this->config = $config_factory->get('media_avportal.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->linkGenerator = $link_generator;
    $this->currentUser = $current_user;
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
      $container->get('entity_type.manager'),
      $container->get('link_generator'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'responsive_image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $responsive_image_options = [];
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface[] $responsive_image_styles */
    $responsive_image_styles = $this->entityTypeManager->getStorage('responsive_image_style')->loadMultiple();
    foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
      if ($responsive_image_style->hasImageStyleMappings()) {
        $responsive_image_options[$machine_name] = $responsive_image_style->label();
      }
    }

    $element['responsive_image_style'] = [
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style') ?: NULL,
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $responsive_image_style = $this->entityTypeManager->getStorage('responsive_image_style')->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);
    }
    else {
      $summary[] = t('Select a responsive image style.');
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

    // We don't want this formatter used if the Responsive Image module is not
    // installed.
    if (!\Drupal::moduleHandler()->moduleExists('responsive_image')) {
      return FALSE;
    }

    // We don't want to use this formatter if there are no responsive image
    // styles on the site.
    if (empty(\Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple())) {
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

    $style_id = $this->getSetting('responsive_image_style');
    if (!$style_id) {
      return $elements;
    }

    $responsive_image_style = $this->entityTypeManager->getStorage('responsive_image_style')->load($style_id);
    if (!$responsive_image_style instanceof ResponsiveImageStyleInterface) {
      return $elements;
    }

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($responsive_image_style);
    if ($responsive_image_style->hasImageStyleMappings()) {
      $image_styles = $responsive_image_style->getImageStyleIds();
      $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple($image_styles);
      foreach ($image_styles as $image_style) {
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

    $responsive_image_style = $this->settings['responsive_image_style'] ?? NULL;
    $theme = 'image';
    if (!empty($responsive_image_style)) {
      $responsive_image_style = $this->entityTypeManager->getStorage('responsive_image_style')->load($responsive_image_style);
      if ($responsive_image_style instanceof ResponsiveImageStyle) {
        $theme = 'responsive_image';
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

    if ($theme === 'responsive_image') {
      $build['#responsive_image_style_id'] = $responsive_image_style->id();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('responsive_image_style');
    if (!$style_id) {
      return $dependencies;
    }

    $responsive_image_style = $this->entityTypeManager->getStorage('responsive_image_style')->load($style_id);
    if (!$responsive_image_style instanceof ResponsiveImageStyleInterface) {
      return $dependencies;
    }

    $dependencies[$responsive_image_style->getConfigDependencyKey()][] = $responsive_image_style->getConfigDependencyName();

    return $dependencies;
  }

}
