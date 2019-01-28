<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\media_avportal\AvPortalClientInterface;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates AvPortal resource URLs.
 */
class AvPortalResourceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The AV portal client.
   *
   * @var \Drupal\media_avportal\AvPortalClientInterface
   */
  protected $avPortalClient;

  /**
   * Constructs a new AvPortalResourceConstraintValidator.
   *
   * @param \Drupal\media_avportal\AvPortalClientInterface $avPortalClient
   *   The AV portal client.
   */
  public function __construct(AvPortalClientInterface $avPortalClient) {
    $this->avPortalClient = $avPortalClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_avportal.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();
    /** @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortalVideoSource $source */
    $source = $media->getSource();

    if (!($source instanceof MediaAvPortalSourceInterface)) {
      throw new \LogicException('Media source must implement ' . MediaAvPortalSourceInterface::class);
    }

    $reference = $source->getSourceFieldValue($media);
    $resource = $this->avPortalClient->getResource($reference);

    if ($resource === NULL) {
      $this->context->addViolation($constraint->message, ['%ref%' => $reference]);
    }
  }

}
