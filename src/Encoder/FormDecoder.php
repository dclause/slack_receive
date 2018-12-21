<?php

namespace Drupal\slack_receive\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Decodes url-form-encoded data.
 *
 * @ingroup slack_receive
 */
class FormDecoder implements DecoderInterface {

  /**
   * The formats that this Decoder supports.
   *
   * @var array
   */
  protected static $format = ['form'];

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    parse_str($data, $result);
    return $result;
  }

}