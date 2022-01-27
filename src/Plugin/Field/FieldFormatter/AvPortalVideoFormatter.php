<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\AvPortalResource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalVideoSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'avportal_video' formatter.
 *
 * @FieldFormatter(
 *   id = "avportal_video",
 *   label = @Translation("AV Portal Video iframe"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class AvPortalVideoFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The default width.
   */
  public const DEFAULT_WIDTH = 640;

  /**
   * The default height.
   */
  public const DEFAULT_HEIGHT = 390;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AvPortalVideoFormatter instance.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\media_avportal\AvPortalClientInterface $avPortalClient
   *   The AV Portal client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory, AvPortalClientInterface $avPortalClient, ConfigFactoryInterface $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('media');
    $this->avPortalClient = $avPortalClient;
    $this->configFactory = $configFactory;
    $this->config = $configFactory->get('media_avportal.settings');
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
      $container->get('logger.factory'),
      $container->get('media_avportal.client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'max_width' => self::DEFAULT_WIDTH,
      'max_height' => self::DEFAULT_HEIGHT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width'),
      '#default_value' => $this->getSetting('max_width'),
      '#size' => 5,
      '#maxlength' => 5,
      '#field_suffix' => $this->t('pixels'),
      '#min' => 0,
    ];

    $form['max_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum height'),
      '#default_value' => $this->getSetting('max_height'),
      '#size' => 5,
      '#maxlength' => 5,
      '#field_suffix' => $this->t('pixels'),
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
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
    return $media_type && $media_type->getSource() instanceof MediaAvPortalVideoSource;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Renders a single field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The individual field item.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   The Drupal element.
   */
  protected function viewElement(FieldItemInterface $item, string $langcode): array {
    $max_width = $this->getSetting('max_width');
    $max_height = $this->getSetting('max_height');

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

    $language_map = $this->configFactory->get('language.mappings')->get('map') ?? [];
    $mapped = array_search($langcode, $language_map);
    if ($mapped !== FALSE) {
      $langcode = $mapped;
    }

    // Prepare the src of the iframe.
    $query = [
      'ref' => $resource_ref,
      'lg' => strtoupper($langcode),
      // @todo What are those default parameters ? Where is the documentation ?
      'sublg' => 'none',
      'autoplay' => 'true',
      'tin' => 10,
      'tout' => 59,
    ];

    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
         // We are not using Html::getUniqueID() because the function changes
        // the ID.
        'id' => 'videoplayer' . $resource_ref,
        'src' => Url::fromUri($this->config->get('iframe_base_uri'), ['query' => $query])->toString(),
        'frameborder' => 0,
        'scrolling' => FALSE,
        'allowtransparency' => TRUE,
        'allowfullscreen' => TRUE,
        'webkitallowfullscreen' => TRUE,
        'mozallowfullscreen' => TRUE,
        'width' => $max_width,
        'height' => $max_height,
        'class' => 'media-avportal-content',
      ],
      '#attached' => [
        'library' => [
          'media_avportal/avportal_video.formatter',
        ],
      ],
    ];
  }

}
