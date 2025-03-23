<?php

/**
 * @file
 * Implement hooks_views_data_alter related to views_table_cs_download_csv.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_table_cs_download_csv_views_data_alter(array &$data) {
  $data['views']['views_table_cs_download_csv_button'] = [
    'title' => t('Download table as csv button'),
    'help' => t('Adds a button to download a table as csv. Currently supporting the views table from core and <a href="https://drupal.org/project/views_flipped_table">flipped table</a>.'),
    'area' => [
      'id' => 'views_table_cs_download_csv_button',
    ],
  ];
}
