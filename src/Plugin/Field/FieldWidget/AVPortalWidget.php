<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalInterface;

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
    $message = $this->t('You can link to media from AV Portal entering a URL of the form https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=[REF]');

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
   * Validate.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   True is the element has been validated correctly, False otherwise.
   */
  public static function validate(array $element, FormStateInterface $form_state) {
    $value = $element['value']['#value'];

    // @todo Yes, move this to constraint. i.e no need for this method here.
    // Also validate that the element exists in the service.

    $listPatterns = [
      // url: http://ec.europa.eu/avservices/video/player.cfm.
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i',
      // url: http://ec.europa.eu/avservices/play.cfm.
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i',
    ];

    foreach ($listPatterns as $pattern) {
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
        preg_match('/([0-9]+)/', $url['query']['ref'], $matches);

        $value['value'] = 'I-' . $matches[0];

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

    return MediaType::load($target_bundle)->getSource() instanceof MediaAvPortalInterface;
  }

}
