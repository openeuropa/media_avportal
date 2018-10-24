<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Base class for Media AV Portal functional JavaScript tests.
 */
class MediaAVPortalCreateTypeTest extends WebDriverTestBase {

  use MediaFunctionalTestTrait;
  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'views_ui',
    'media_avportal',
  ];

  /**
   * Test media type "Media AV Portal" creation.
   */
  public function testMediaTypeCreation() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('<front>');

    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    $label = 'Media AV Portal';
    // $mediaTypeMachineName = str_replace(' ', '_', strtolower($label));
    $this->drupalGet('admin/structure/media/add');

    // Fill in a label to the media type.
    $page->fillField('label', $label);
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.machine-name-value')
    );

    $assert_session->selectExists('Media source')->selectOption('media_avportal');

    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]')
    );

    $page->pressButton('Save');
    $page->hasContent('The media type Media AV Portal has been added.');
  }

}
