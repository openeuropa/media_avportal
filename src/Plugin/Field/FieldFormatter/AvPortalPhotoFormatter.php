<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\AvPortalResource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhoto;
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
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $media_type_id = $field_definition->getTargetBundle();

      if ($media_type_id !== NULL) {
        $media_type = MediaType::load($media_type_id);

        return $media_type && $media_type->getSource() instanceof MediaAvPortalPhoto;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item);
    }

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

    return [
      '#theme' => 'image',
      '#attributes' => ['class' => 'avportal-photo'],
      '#uri' => $this->config->get('photos_base_uri') . $resource->getPhotoUri(),
    ];
  }

}
