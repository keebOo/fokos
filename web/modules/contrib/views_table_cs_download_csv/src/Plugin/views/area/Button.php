<?php

namespace Drupal\views_table_cs_download_csv\Plugin\views\area;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\style\Table;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Area handler to add a button to download views tables as csv.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_table_cs_download_csv_button")
 */
final class Button extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setModuleHandler($container->get('module_handler'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);
    if (!$this->hasSupportedStylePlugin()) {
      $style_plugin_id = $this->view->style_plugin->getPluginId();
      $form_state->setError($form, $this->t('The button area handler does not support "@plugin"', [
        '@plugin' => $style_plugin_id,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE): array {
    if (!$this->hasSupportedStylePlugin() || $empty) {
      return [];
    }

    $title = $this->view->getTitle();
    return [
      '#theme' => 'views_table_cs_download_csv_button',
      '#view_id' => $this->view->id(),
      '#display_id' => $this->view->current_display,
      '#filename' => $title ? strtolower(Html::cleanCssIdentifier($title)) : 'table',
    ];
  }

  /**
   * Checks if the views has supported style plugin.
   *
   * @return bool
   *   TRUE when the style plugin is supported.
   */
  protected function hasSupportedStylePlugin(): bool {
    $style_plugin = $this->view->getStyle() ?? NULL;
    return $style_plugin instanceof Table;
  }

}
