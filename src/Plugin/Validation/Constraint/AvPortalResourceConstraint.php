<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value represents a valid AV Portal resource URL.
 *
 * @Constraint(
 *   id = "avportal_resource",
 *   label = @Translation("AvPortal resource", context = "Validation"),
 *   type = {"link", "string", "string_long"}
 * )
 */
class AvPortalResourceConstraint extends Constraint {

  /**
   * The error message if the URL does not match AV Portal provider.
   *
   * @var string
   */
  public $message = 'The given URL does not match an AV Portal URL.';

}
