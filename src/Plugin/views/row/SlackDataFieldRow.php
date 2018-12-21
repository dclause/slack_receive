<?php

namespace Drupal\slack_receive\Plugin\views\row;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "slack_data_field",
 *   title = @Translation("Slack fields"),
 *   help = @Translation("Use fields with slack format helper."),
 *   display_types = {"data"}
 * )
 */
class SlackDataFieldRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * Stores an array of options to determine if the raw field output is used.
   *
   * @var array
   */
  protected $rawOutputOptions = [];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['field_options'])) {
      $options = (array) $this->options['field_options'];
      // Prepare an array of raw output field options.
      $this->rawOutputOptions = static::extractFromOptionsArray('raw_output', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_options'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Add custom options.
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);

    // Build the input field option.
    $input_label_descr = empty($field_labels) ? '<b>' . $this->t('Warning') . ': </b> ' . $this->t('Requires at least one field in the view.') . '<br/>' : '';
    $form['title_field'] = [
      '#title' => $this->t('Use this field as title'),
      '#type' => 'select',
      '#description' => new HtmlEscapedText($input_label_descr),
      '#default_value' => $this->options['title_field'],
      '#disabled' => empty($field_labels),
      '#required' => TRUE,
      '#options' => $field_labels,
    ];

    $form['text_field'] = [
      '#title' => $this->t('Combine those fields in content'),
      '#type' => 'select',
      '#description' => new HtmlEscapedText($input_label_descr),
      '#default_value' => $this->options['text_field'],
      '#disabled' => empty($field_labels),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => $field_labels,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $output = [];

    foreach ($this->view->field as $id => $field) {
      // If the raw output option has been set, just get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = $field->getValue($row);
      }
      // Otherwise, pass this through the field advancedRender() method.
      else {
        $value = $field->advancedRender($row);
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude']) && $alias = $this->getFieldKeyAlias($id)) {
        $output[$alias] .= ($output[$alias] ? "\n" : '') . $value;
      }
    }

    // Add slack specific data.
    $output['mrkdwn_in'] = ['title', 'text'];
    if ($this->view->style_plugin->options['color']) {
      $output['color'] = $this->view->style_plugin->options['color'];
    }

    return $output;
  }

  /**
   * Return an alias for a field ID, as set in the options form.
   *
   * @param string $id
   *   The field id to lookup an alias for.
   *
   * @return string
   *   The matches user entered alias, or the original ID if nothing is found.
   */
  public function getFieldKeyAlias($id) {
    if ($this->options['title_field'] == $id) {
      return 'title';
    }
    if (in_array($id, $this->options['text_field'])) {
      return 'text';
    }
    return NULL;
  }

  /**
   * Extracts a set of option values from a nested options array.
   *
   * @param string $key
   *   The key to extract from each array item.
   * @param array $options
   *   The options array to return values from.
   *
   * @return array
   *   A regular one dimensional array of values.
   */
  protected static function extractFromOptionsArray($key, $options) {
    return array_map(function ($item) use ($key) {
      return isset($item[$key]) ? $item[$key] : NULL;
    }, $options);
  }

}
