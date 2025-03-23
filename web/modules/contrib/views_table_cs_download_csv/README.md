# Client side download of views table as csv

Implements a Views area plugin that adds a button to trigger the download of
supported views table styles as a CSV.

Note that the download happens on the client side. This means that if the table
has multiple pages, only the page being displayed would be downloaded. If you
would like a module that can download all the data, please see the
[views_data_export](https://www.drupal.org/project/views_data_export).

## Requirements

- The views module included with the drupal core module

## Supported views table style plugins

- *Views table* style plugin (comes with core)
- *[Views flipped table](https://www.drupal.org/project/view_flipped_table)*
