/**
 * @file
 * Behavior for handling guest dismissal confirmation.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.fokosDimissioni = {
    attach: function (context, settings) {
      once('fokosDimissioni', '.fokos-dimissioni-link', context).forEach(function (link) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          
          if (window.confirm('Sei sicuro di voler dimettere questo ospite dalla struttura?')) {
            window.location.href = this.href;
          }
        });
      });
    }
  };
})(Drupal);
