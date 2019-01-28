<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalPhotoSource;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalVideoSource;

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
class AvPortalWidget extends StringTextfieldWidget {

  const AVPORTAL_VIDEO = 'video';
  const AVPORTAL_PHOTO = 'photo';

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $message = $this->t('You can link to media from AV Portal by entering a URL in the formats https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=[REF] or https://ec.europa.eu/avservices/photo/photoDetails.cfm?sitelang=en&ref=[REF]');

    $element['value']['#description'] = $message;

    if (!empty($element['#value']['#description'])) {
      $element['value']['#description'] = [
        '#theme' => 'item_list',
        '#items' => [$element['value']['#description'], $message],
      ];
    }

    // Custom validation depending on the type.
    $target_bundle = $this->fieldDefinition->getTargetBundle();
    $source = MediaType::load($target_bundle)->getSource();

    if ($source instanceof MediaAvPortalPhotoSource) {
      $element['#element_validate'] = [
        [static::class, 'validatePhoto'],
      ];
    }
    elseif ($source instanceof MediaAvPortalVideoSource) {
      $element['#element_validate'] = [
        [static::class, 'validateVideo'],
      ];
    }

    $matches = [];

    if (!empty($element['value']['#default_value'])) {
      // Video.
      if (preg_match('/I\-(\d+)/', $element['value']['#default_value'])) {
        $element['value']['#default_value'] = 'https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=' . $element['value']['#default_value'];
      }
      // Photo.
      elseif (preg_match('/P\-(\d+)\/(\d+)\-(\d+)/', $element['value']['#default_value'], $matches)) {
        $element['value']['#default_value'] = 'https://ec.europa.eu/avservices/photo/photoDetails.cfm?sitelang=en&ref=' . $matches[1] . '#' . ($matches[3] - 1);
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validatePhoto(array $element, FormStateInterface $form_state, $validated_type): void {
    self::validate($element, $form_state, self::AVPORTAL_PHOTO);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateVideo(array $element, FormStateInterface $form_state, $validated_type): void {
    self::validate($element, $form_state, self::AVPORTAL_VIDEO);
  }

  /**
   * {@inheritdoc}
   */
  public static function validate(array $element, FormStateInterface $form_state, $validated_type): void {
    $value = $element['value']['#value'];

    $patterns = self::getPatterns();

    foreach ($patterns as $pattern => $type) {
      if (preg_match($pattern, $value) && $type == $validated_type) {
        return;
      }
    }

    $form_state->setError($element['value'], t('Invalid URL format specified.'));
  }

  /**
   * Get valid url patterns and their type.
   *
   * @return array
   *   The supported patterns.
   */
  protected static function getPatterns() {
    return [
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i' => self::AVPORTAL_VIDEO,
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i' => self::AVPORTAL_VIDEO,
      '@ec\.europa\.eu/avservices/photo/photoDetails.cfm?(.+)@i' => self::AVPORTAL_PHOTO,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Converts the full url used in the widget to store only the proper ref
    // in the field value.
    foreach ($values as $value) {

      // Detects which pattern we are using setting its type: VIDEO or Photo.
      $patterns = self::getPatterns();
      $type = NULL;
      foreach ($patterns as $pattern => $pattern_type) {
        if (preg_match($pattern, $value['value'])) {
          $type = $pattern_type;
        }
      }

      $url = UrlHelper::parse($value['value']);

      if (!isset($url['query']['ref'])) {
        return $value;
      }

      if ($type == self::AVPORTAL_VIDEO) {
        preg_match('/(\d+)/', $url['query']['ref'], $matches);

        // The reference should be in the format I-xxxx where x are numbers.
        // Sometimes no dash is present, so we have to normalise the reference
        // back.
        if (isset($matches[0])) {
          $value['value'] = 'I-' . $matches[0];
        }
      }
      elseif ($type == self::AVPORTAL_PHOTO) {
        preg_match('/(\d+)/', $url['query']['ref'], $matches);
        // The reference should be in the format P-xxxx-00-yy where xxxx and
        // yy are numbers.
        // Sometimes no dash is present, so we have to normalise the reference
        // back.
        if (isset($matches[0])) {
          $value['value'] = 'P-' . $matches[0] . '/00-' . sprintf('%02d', $url['fragment'] + 1);
        }
      }

      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_bundle = $field_definition->getTargetBundle();

    if (!$target_bundle || !parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    $source = MediaType::load($target_bundle)->getSource();
    return ($source instanceof MediaAvPortalSourceInterface);
  }

}
