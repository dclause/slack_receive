<?php

/**
 * Respond to a slash command
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * entity content structure has been built.
 *
 * If a module wishes to act on the rendered HTML of the entity rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * the particular entity type template, if there is one (e.g., node.html.twig).
 *
 * See the @link themeable Default theme implementations topic @endlink and
 * drupal_render() for details.
 *
 * @param string $command
 *   The slash command.
 * @param string $text
 *   The slash command content such as provided.
 *
 * @return array
 *   An array representing a valid Slack Message API message
 *   @see https://api.slack.com/docs/message-formatting
 *
 * @ingroup slack_receive
 */
function hook_slack_receive_slash_command($command, $text) {
  $result = [];

  // React to /repeat command.
  if ($command == 'repeat') {

    // Answser must follow Slash message API format
    // @see https://api.slack.com/docs/message-formatting
    // NOTE: you can use BBCode as per documentation, or HTML as below,
    // it will be converted for you.
    // @see \Drupal\slack_receive\Encoder\SlackJsonEncoder
    $result = [
      'text' => t('Repeat bot: <i>@text</i>', ['@text' => $text]),
    ];
  }
  return $result;
}