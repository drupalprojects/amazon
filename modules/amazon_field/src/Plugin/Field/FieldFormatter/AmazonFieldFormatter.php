<?php

namespace Drupal\amazon_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amazon\Amazon;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'amazon_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "amazon_field_formatter",
 *   label = @Translation("Amazon field formatter"),
 *   field_types = {
 *     "amazon_asin_field"
 *   }
 * )
 */
class AmazonFieldFormatter extends FormatterBase {

  /**
   * Contians a list of display options for this formatter.
   *
   * @var array
   */
  protected $templateOptions = [];

  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->templateOptions = [
      'inline' => $this->t('Item title'),
      'image_small' => $this->t('Small image'),
      'image_medium' => $this->t('Medium image'),
      'image_large' => $this->t('Large image'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaultMaxAge = \Drupal::config('amazon.settings')->get('default_max_age');
    if (is_null($defaultMaxAge)) {
      throw new \InvalidArgumentException('Missing Amazon settings: default max age.');
    }

    return array(
      'max_age' => $defaultMaxAge,
      'template' => 'amazon_image_large',
  ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $defaultMaxAge = \Drupal::config('amazon.settings')->get('default_max_age');

    $form['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max age for cached results'),
      '#description' => $this->t('The number of seconds that the system should cache the results from Amazon\'s servers. Leave blank to use the default max age set on the <a href=":url">Amazon settings page</a>, currently set at @default_max_age seconds.', [
        ':url' => Url::fromRoute('amazon.settings_form'),
        '@default_max_age' => $defaultMaxAge
      ]),
      '#default_value' => ($this->getSetting('max_age') == $defaultMaxAge) ? '' : $this->getSetting('max_age'),
    ];
    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Display item as'),
      '#description' => $this->t('By default, all options will link to the item in the Amazon store tagged with your Associates ID.'),
      '#options' => $this->templateOptions,
      '#default_value' => $this->getSetting('template'),
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Cache max age: @max_age', ['@max_age' => $this->getSetting('max_age')]);
    $summary[] = $this->t('Display as: @template', ['@template' => $this->templateOptions[$this->getSetting('template')]]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $asins = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $asins[$item->value] = $item->value;
      }
    }

    $associatesId = \Drupal::config('amazon.settings')->get('associates_id');
    $amazon = new Amazon($associatesId);
    $results = $amazon->lookup($asins);

    // No results from Amazon.
    if (empty($results[0])) {
      return $elements;
    }

    $max_age = $this->getSetting('max_age');
    $basicBuild = [
      '#max_age' => $max_age,
    ];
    // Use the correct Twig template based on the "template" specified.
    switch (strtolower($this->getSetting('template'))) {
      case 'inline':
        $basicBuild['#theme'] = 'amazon_inline';
        break;

      case 'image_small':
        $basicBuild['#theme'] = 'amazon_image';
        $basicBuild['#size'] = 'small';
        break;

      case 'image_medium':
        $basicBuild['#theme'] = 'amazon_image';
        $basicBuild['#size'] = 'medium';
        break;

      case 'image_large':
        $basicBuild['#theme'] = 'amazon_image';
        $basicBuild['#size'] = 'large';
        break;

      default:
        // Unknown template specified.
        return $elements;
    }

    foreach ($results as $result) {
      $elements[$delta] = $basicBuild + ['#results' => $result];
    }

    return $elements;
  }

}
