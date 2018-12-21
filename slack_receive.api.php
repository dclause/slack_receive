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
 * @ingroup slack_receive
 */
function hook_slack_receive_slash_command($command, $text) {
  $result = [];

  // React to /repeat command.
  if ($command == 'repeat') {
    $result[] = t('Repeat: @text', ['@text' => $text]);
  }
  $result;
}