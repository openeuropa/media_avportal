<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalVideo;

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
class AVPortalWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $message = $this->t('You can link to media from AV Portal by entering a URL in the format https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=[REF]');

    $element['value']['#description'] = $message;

    if (!empty($element['#value']['#description'])) {
      $element['value']['#description'] = [
        '#theme' => 'item_list',
        '#items' => [$element['value']['#description'], $message],
      ];
    }

    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    if (!empty($element['value']['#default_value'])) {
      $element['value']['#default_value'] = 'https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=' . $element['value']['#default_value'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validate(array $element, FormStateInterface $form_state): bool {
    $value = $element['value']['#value'];

    $patterns = [
      // url: http://ec.europa.eu/avservices/video/player.cfm.
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i',
      // url: http://ec.europa.eu/avservices/play.cfm.
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $value)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return array_map(
      function (array $value) {
        $url = UrlHelper::parse($value['value']);

        if (!isset($url['query']['ref'])) {
          return $value;
        }

        // Extract numeric values only.
        preg_match('/(\d+)/', $url['query']['ref'], $matches);

        if (isset($matches[0])) {
          $value['value'] = 'I-' . $matches[0];
        }

        return $value;
      },
      $values
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_bundle = $field_definition->getTargetBundle();

    if (NULL === $target_bundle || !parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    return MediaType::load($target_bundle)->getSource() instanceof MediaAvPortalVideo;
  }

}
