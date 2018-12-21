<?php

namespace Drupal\slack_receive\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;

/**
 * Encode data as Slack JSON.
 *
 * @ingroup slack_receive
 */
class SlackJsonEncoder extends BaseJsonEncoder implements EncoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  protected static $format = ['slack'];

  /**
   * {@inheritdoc}
   */
  public function __construct(JsonEncode $encodingImpl = NULL, JsonDecode $decodingImpl = NULL) {
    // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be
    // embedded into HTML.
    // @see \Symfony\Component\HttpFoundation\JsonResponse
    $json_encoding_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $this->encodingImpl = $encodingImpl ?: new JsonEncode($json_encoding_options);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {

    /**
     * This function will try to convert HTML to slack supported BBCode.
     * @param $item string item to BBCode encode.
     */
    $convertHtmlToBBcode = function (&$item) {
      $converters = [
        // Non-breaking spaces.
        '\xc2\xa0' => ' ',
        // Font styles
        '\<strong.*?\>(\s*?)(.*?)(\s*?)\<\/strong\>' => '*$2*',
        '\<b.*?\>(\s*?)(.*?)(\s*?)\<\/b\>' => '*$2*',
        '\<em.*?\>(\s*?)(.*?)(\s*?)\<\/em\>' => '_$2_',
        '\<i.*?\>(\s*?)(.*?)(\s*?)\<\/i\>' => '_$2_',
        '\<s.*?\>(\s*?)(.*?)(\s*?)\<\/s\>' => '~$2~',
        '\<u.*?\>(\s*?)(.*?)(\s*?)\<\/u\>' => '_$2_',
        // Code formatters
        '\<code.*?\>(\s*?)(.*?)(\s*?)\<\/code\>' => '`$2`',
        '\<pre.*?\>(\s*?)(.*?)(\s*?)\<\/pre\>' => '```$2```',
        // Links
        '\<a.*?href\=\"(.*?)\".*?\>(\s*?)(.*?)(\s*?)\<\/a\>' => '&#x3C;$1|$3&#x3E;',
        // Lists
        '\t*?\<li.*?\>' => ' • ',
        // Newlines
        '\<br.*?\>' => "\n",

        // Falsy/weird HTML convert corrections
        // ex.:  <em><strong>foo </em></strong>bar
        '(\*.*?\*)([^\*\_\~\`])' => '$1 $2',
        '(\_.*?\_)([^\*\_\~\`])' => '$1 $2',
        '(\~.*?\~)([^\*\_\~\`])' => '$1 $2',
        '(\`.*?\`)([^\*\_\~\`])' => '$1 $2',
      ];

      foreach ($converters as $pattern => $replacement) {
        $item = preg_replace('/' . $pattern . '/si', $replacement, $item);
      }
      $item = html_entity_decode(strip_tags($item));
    };

    // Walk through the data and convert HTML to BBCode.
    if (is_array($data)) {
      array_walk_recursive($data, $convertHtmlToBBcode);
    }
    return parent::encode($data, $format, $context);
  }
}
