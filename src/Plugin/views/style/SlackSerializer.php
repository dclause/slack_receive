<?php

namespace Drupal\slack_receive\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for slack serialized output formats.
 *
 * This class extends Serializer style but restrict it to only slack available
 * format.
 *
 * @ingroup slack_receive
 *
 * @ViewsStyle(
 *   id = "slack_serializer",
 *   title = @Translation("Slack Serializer"),
 *   help = @Translation("Serializes views row data using the Slack Serializer component."),
 *   display_types = {"data"}
 * )
 */
class SlackSerializer extends Serializer {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['formats'] = ['default' => ['slack']];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['formats']['#disabled'] = TRUE;

    $form['color'] = [
      '#title' => $this->t('Border color'),
      '#type' => 'color',
      '#description' => $this->t('Left border row displayed on slack'),
      '#default_value' => $this->options['color'],
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $format = $form_state->getValue(['style_options', 'format']);
    $form_state->setValue(['style_options', 'format'], $format);
  }

  /**
   * Returns an array of format options
   *
   * @return string[]
   *   An array of format options. Both key and value are the same.
   */
  protected function getFormatOptions() {
    $formats = ['slack'];
    return array_combine($formats, $formats);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];

    $rows['username'] = $this->view->getTitle();

    // Render the view header.
    foreach ($this->view->header as $header) {
      $headers[] = $this->getRenderer()->render($header->render());
    }
    $rows['text'] = implode("\n", $headers);

    // Render rows.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows['attachments'][] = $this->view->rowPlugin->render($row);
    }

    // If no rows: render empty message.
    if (empty($rows['attachments'])) {
      $rows['attachments'] = [];
      foreach ($this->view->empty as $empty) {
        $rows['attachments'][] = [
          'color' => '#ff0000',
          'text' => $this->getRenderer()->render($empty->render()),
        ];
      }
    }

    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'slack';
      return $this->serializer->serialize($rows, $content_type, ['views_style_plugin' => $this, 'json_encode_options' => JSON_PRETTY_PRINT]);
    }
    return $this->serializer->serialize($rows, $content_type, ['views_style_plugin' => $this]);
  }
}
