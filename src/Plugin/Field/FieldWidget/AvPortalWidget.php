<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'avportal_textfield' widget.
 *
 * @FieldWidget(
 *   id = "avportal_textfield",
 *   label = @Translation("AV Portal URL"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class AvPortalWidget extends StringTextfieldWidget implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The media source for the field.
   *
   * @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface
   */
  protected $source;

  /**
   * Constructs a AvPortalWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $target_bundle = $this->fieldDefinition->getTargetBundle();
    $this->source = $this->entityTypeManager->getStorage('media_type')->load($target_bundle)->getSource();
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Element description.
    $formats = $this->source->getSupportedUrlFormats();
    $message = $this->t('You can link to media from AV Portal by entering a URL in the formats: @formats', ['@formats' => implode(', ', $formats)]);

    $element['value']['#description'] = $message;

    // Custom validation depending on the type.
    if (!empty($element['#value']['#description'])) {
      $element['value']['#description'] = [
        '#theme' => 'item_list',
        '#items' => [$element['value']['#description'], $message],
      ];
    }

    $element['#valid_urls'] = array_keys($this->source->getSupportedUrlPatterns());
    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    if (!empty($element['value']['#default_value'])) {
      $element['value']['#default_value'] = $this->source->transformReferenceToUrl($element['value']['#default_value']);
    }

    return $element;
  }

  /**
   * Validates the AV Portal element for a given type of resource.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see self::formElement()
   */
  public static function validate(array $element, FormStateInterface $form_state): void {
    $value = $element['value']['#value'];

    $patterns = $element['#valid_urls'];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $value)) {
        return;
      }
    }

    $form_state->setError($element['value'], t('Invalid URL format specified.'));
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    // Converts the full url used in the widget to store only the proper ref
    // in the field value.
    foreach ($values as $value) {

      $reference = $this->source->transformUrlToReference($value['value']);

      if (!empty($reference)) {
        $value['value'] = $reference;
      }

      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    $target_bundle = $field_definition->getTargetBundle();

    if (!$target_bundle || !parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    $source = MediaType::load($target_bundle)->getSource();
    return ($source instanceof MediaAvPortalSourceInterface);
  }

}
