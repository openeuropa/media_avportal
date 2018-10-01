<?php

/**
 * @file
 * Definition of MediaAvPortalMedia.
 */

/**
 * Class MediaAvPortalMedia.
 *
 * It proposes the metadata of AVportal media.
 */
class MediaAvPortalMedia {

  const MEDIA_AVPORTAL_TYPE_VIDEO = 'VIDEO';

  const MEDIA_AVPORTAL_TYPE_IMAGE = 'PHOTO';

  /**
   * The language code referred in the consumer system for the media metadata.
   *
   * @var string
   */
  private $metadataDefaultLanguage = '';

  /**
   * The language code referred in the consumer system for the media.
   *
   * @var string
   */
  private $mediaDefaultLanguage = '';

  /**
   * List of the available language versions for the media.
   *
   * @var array
   */
  private $mediaAvailableLanguages = array();

  /**
   * The title set for the media.
   *
   * @var string
   */
  private $mediaDefaultSummary = '';

  /**
   * The other summary translations set for the media.
   *
   * @var array
   */
  private $mediaSummaryAlternativeTranslations = array();

  /**
   * The title set for the media.
   *
   * @var string
   */
  private $mediaDefaultTitle = '';

  /**
   * The other title translations set for the media.
   *
   * @var array
   */
  private $mediaTitleAlternativeTranslations = array();

  /**
   * The reference of the media.
   *
   * @var string
   */
  private $mediaRef = '';

  /**
   * The media thumbnail URL.
   *
   * @var string
   */
  private $mediaDefaultThumbnailUrl = '';

  /**
   * The media thumbnail URL for other languages.
   *
   * @var array
   */
  private $mediaTranslatedThumbnailUrls = array();

  /**
   * The media type.
   *
   * @var string
   */
  private $mediaType = self::MEDIA_AVPORTAL_TYPE_VIDEO;

  /**
   * MediaAvPortalMedia constructor.
   *
   * @param array $serviceResponse
   *   The object with data sent by the AVportal service.
   * @param string $metadataDefaultLanguage
   *   The language code used as reference in the consumer system.
   */
  public function __construct(array $serviceResponse, $metadataDefaultLanguage = 'en') {
    $this->metadataDefaultLanguage = $metadataDefaultLanguage;
    $this->parseServiceMedia($serviceResponse);
  }

  /**
   * Initialize the default language of the media metadata.
   *
   * @param array $serviceMedia
   *   The media data retrieved by the AVPortal service.
   */
  private function initMetadataDefaultLanguage(array $serviceMedia) {
    // Init done at object instantiation time.
    if (!empty($this->mediaDefaultLanguage)) {
      return;
    }

    $titleFromService = $serviceMedia['titles_json'];
    $setLanguages = array_keys($titleFromService);

    if (in_array('EN', $setLanguages)) {
      $this->mediaDefaultLanguage = 'en';
      return;
    }

    if (in_array('INT', $setLanguages)) {
      $this->mediaDefaultLanguage = LANGUAGE_NONE;
      return;
    }

    $defaultLanguage = reset($setLanguages);
    $this->mediaDefaultLanguage = drupal_strtolower($defaultLanguage);

  }

  /**
   * Gets the translated metadata value for the media language.
   *
   * @param array $metadataValues
   *   Associative array containing the metadata translated values;
   *   the key is the translation language code in upper case.
   * @param string $language
   *   (Optional) The language corresponding to metadata to retrieved.
   *   If not set, the metadataDefaultLanguage value will be taken.
   *
   * @return string
   *   The translated value in the language specified by the
   *   $metadataDefaultLanguage parameter. If not found, the
   *   "undefined language" value is returned, otherwise in the English value
   *   or the first returned value if the English one is not present.
   */
  private function getTranslatableMediaMetadata(array $metadataValues, $language = '') {
    $searchedLanguage = $this->metadataDefaultLanguage;
    if (!empty($language)) {
      $searchedLanguage = $language;
    }
    $searchedLanguage = drupal_strtoupper($searchedLanguage);

    if (!empty($metadataValues[$searchedLanguage])) {
      return $metadataValues[$searchedLanguage];
    }

    if (!empty($metadataValues['INT'])) {
      return $metadataValues['INT'];
    }

    if (!empty($metadataValues['EN'])) {
      return $metadataValues['EN'];
    }

    return reset($metadataValues);
  }

  /**
   * Gets other translation for the metadata value.
   *
   * @param array $metadataValues
   *   Associative array containing the metadata translated values;
   *   the key is the translation language code in upper case.
   *
   * @return array
   *   Associative array with the translations of a media metadata value other
   *   than the one of the reference language
   */
  private function getOtherMetadataTranslations(array $metadataValues) {
    $values = array();

    foreach ($metadataValues as $upper_lang => $value) {
      $currentLanguage = drupal_strtolower($upper_lang);
      $values[$currentLanguage] = $value;
    }

    return $values;
  }

  /**
   * Parses the media_json data of the AVportal response for the thumbnail URL.
   *
   * @param array $mediaJson
   *   The media_json data array.
   *
   * @return string
   *   The thumbnail URL related to the media.
   */
  protected function parseThumbnailMediaJson(array $mediaJson) {
    if (empty($mediaJson)) {
      return '';
    }

    // Retrieve the media parameters in order to retrieve the thumbnail.
    // The "16:9" format must be used in priority but if not available, the
    // "1:1" format must be used then but it is not available, then use the
    // "4:3" one.
    $formattedMedia = reset($mediaJson);
    if (!empty($mediaJson['16:9'])) {
      $formattedMedia = (array) $mediaJson['16:9'];
    }
    elseif (!empty($mediaJson['1:1'])) {
      $formattedMedia = (array) $mediaJson['1:1'];
    }

    if (empty($formattedMedia)) {
      return '';
    }

    $this->mediaDefaultLanguage = reset($this->mediaAvailableLanguages);
    if (in_array($this->metadataDefaultLanguage, $this->mediaAvailableLanguages)) {
      $this->mediaDefaultLanguage = $this->metadataDefaultLanguage;
    }
    elseif (in_array('en', $this->mediaAvailableLanguages)) {
      $this->mediaDefaultLanguage = 'en';
    }

    // Let's convert the "undefined language" value in the Drupal one.
    if ($this->mediaDefaultLanguage == 'int') {
      $this->mediaDefaultLanguage = LANGUAGE_NONE;
    }

    // Retrieve the thumbnail from the "THUMB" parameter. If not available,
    // Use the "IMAGE" parameter.
    // if none of them is available, leave the calling system choose what it
    // displays instead.
    foreach ($this->mediaAvailableLanguages as $language) {
      $translatedMedia = $this->getTranslatableMediaMetadata($formattedMedia, $language);

      // Let's convert the "undefined language" value in the Drupal one.
      if ($language == 'int') {
        $language = LANGUAGE_NONE;
      }

      $url = '';
      if (isset($translatedMedia['THUMB'])) {
        $url = $translatedMedia['THUMB'];
      }
      elseif (isset($translatedMedia['IMAGE'])) {
        $url = $translatedMedia['IMAGE'];
      }

      if ($language == $this->mediaDefaultLanguage) {
        $this->mediaDefaultThumbnailUrl = $url;
      }

      $this->mediaTranslatedThumbnailUrls[$language] = $url;
    }
  }

  /**
   * Parses the array of the AVportal service to set object properties.
   *
   * @param array $serviceMedia
   *   An associative array with the media metadata returned by the service.
   */
  protected function parseServiceMedia(array $serviceMedia) {
    // Media reference.
    $this->mediaRef = '';
    if (!empty($serviceMedia['ref'])) {
      $this->mediaRef = $serviceMedia['ref'];
    }

    $this->initMetadataDefaultLanguage($serviceMedia);

    // Treat the title.
    $this->mediaDefaultTitle = '';
    $this->mediaDefaultTitle = $this->getTranslatableMediaMetadata($serviceMedia['titles_json']);
    $this->mediaTitleAlternativeTranslations = $this->getOtherMetadataTranslations($serviceMedia['titles_json']);

    // Available languages for the video.
    if (!empty($serviceMedia['languages'])) {
      foreach ($serviceMedia['languages'] as $language) {
        $this->mediaAvailableLanguages[] = drupal_strtolower($language);

        // Let's add the regional specification language like "pt-pt" that
        // exist in the predefined list of language, see
        // _locale_get_predefined_list().
        switch ($language) {
          case 'pt':
            // Portuguese, Portugal (Português).
            $this->mediaAvailableLanguages[] = 'pt-pt';
            // Portuguese, Brazil (Português).
            $this->mediaAvailableLanguages[] = 'pt-br';
            break;

          case 'en':
            $this->mediaAvailableLanguages[] = 'en-gb';
            break;

          case 'de':
            // Swiss German.
            $this->mediaAvailableLanguages[] = 'gsw-berne';
            break;

          case 'zh':
            // Chinese (Simplified).
            $this->mediaAvailableLanguages[] = 'zh-Hans';
            // Chinese (Traditional).
            $this->mediaAvailableLanguages[] = 'zh-Hant';
            break;
        }
      }
    }

    // Treat the summary.
    $this->mediaDefaultSummary = '';
    if (!empty($serviceMedia['summary_json'])) {
      $this->mediaDefaultSummary = $this->getTranslatableMediaMetadata($serviceMedia['summary_json']);
      $this->mediaSummaryAlternativeTranslations = $this->getOtherMetadataTranslations($serviceMedia['summary_json']);
    }

    // Treat the media type.
    $this->mediaType = self::MEDIA_AVPORTAL_TYPE_VIDEO;
    if (!empty($serviceMedia['type']) && (self::MEDIA_AVPORTAL_TYPE_VIDEO != drupal_strtoupper($serviceMedia['type']))) {
      $this->mediaType = self::MEDIA_AVPORTAL_TYPE_IMAGE;
    }

    // Treat the thumbnail URL.
    if (!empty($serviceMedia['media_json'])) {
      $mediaJson = (array) $serviceMedia['media_json'];
      $this->parseThumbnailMediaJson($mediaJson);
    }
  }

  /**
   * Gets the summary of the media.
   *
   * The summary will be returned in the reference language set for the object.
   *
   * @return string
   *   The summary.
   */
  public function getMediaSummary() {
    return $this->mediaDefaultSummary;
  }

  /**
   * Gets the title of the media.
   *
   * The title will be returned in the reference language set for the object.
   *
   * @return string
   *   The title.
   */
  public function getMediaTitle() {
    return $this->mediaDefaultTitle;
  }

  /**
   * Gets the default language of the media metadata.
   *
   * @return string
   *   The language code of the media metadata.
   */
  public function getMetadataDefaultLanguage() {
    return $this->metadataDefaultLanguage;
  }

  /**
   * Gets the default language of the media.
   *
   * @return string
   *   The language code of the media.
   */
  public function getMediaDefaultLanguage() {
    return $this->mediaDefaultLanguage;
  }

  /**
   * Gets the available languages available for the media.
   *
   * @return array
   *   The list of the language codes.
   */
  public function getMediaAvailableLanguages() {
    return $this->mediaAvailableLanguages;
  }

  /**
   * Gets the reference of the media.
   *
   * @return string
   *   The reference.
   */
  public function getMediaRef() {
    return $this->mediaRef;
  }

  /**
   * Gets the thumbnail of the media.
   *
   * @return string|bool
   *   The thumbnail of the media; or false if the mediat is not a video.
   */
  public function getMediaDefaultThumbnailUrl() {
    return $this->mediaDefaultThumbnailUrl;
  }

  /**
   * Gets the type of the media as defined in AVportal.
   *
   * @return string
   *   The media type; the constant MEDIA_AVPORTAL_TYPE_VIDEO or
   *   MEDIA_AVPORTAL_TYPE_IMAGE.
   */
  public function getMediaType() {
    return $this->mediaType;
  }

  /**
   * Checks if the media is a video.
   *
   * @return bool
   *   TRUE if it is a video.
   */
  public function isVideoType() {
    return ($this->getMediaType() == self::MEDIA_AVPORTAL_TYPE_VIDEO);
  }

  /**
   * Gets the thumbnail of the media in the other languages than teh default.
   *
   * @return string|bool
   *   The thumbnail of the media; or false if the media is not a video.
   */
  public function getMediaTranslatedThumbnailUrls() {
    return $this->mediaTranslatedThumbnailUrls;
  }

}
