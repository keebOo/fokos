/**
 * @file
 * Implement client side logic to initiate the download of table as csv.
 */
(function (Drupal, once) {

  "use strict";

  const initiateDownload = function (csv, filename) {
    const csvFile = new Blob([csv], {type: 'text/csv'});
    const downloadLink = document.createElement('a');

    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none !important';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    downloadLink.remove();
  };

  const buttonClickHandler = function (e) {
    e.preventDefault();

    // Disabling the button and changing the text to show the user that
    // something is happening.
    e.currentTarget.disabled = true;
    const currentInnerHtml = e.currentTarget.innerHTML;
    e.currentTarget.innerHTML = Drupal.t('Processing');

    const data = JSON.parse(this.dataset.viewsTableClientSideDownloadCsvButton);
    const table = document.querySelector(`[data-views-table-client-side-download-csv-target-table="${data.targetTable}"]`);

    let csv = [];
    table.querySelectorAll('tr').forEach(tr => {
      let row = [];
      tr.querySelectorAll('td, th').forEach(cell => {
        row.push(`"${cell.innerText}"`);
      });
      csv.push(row.join(','));
    });

    let filename = data.filename ? data.filename : `${data.view_id}--${data.display_id}--table`;
    filename = `${filename}.csv`;

    initiateDownload(csv.join('\n'), filename);

    // Enabling the button back and restore the original inner html.
    e.currentTarget.disabled = false;
    e.currentTarget.innerHTML = currentInnerHtml;
  };

  Drupal.behaviors.viewsTableClientSideDownloadCsvInitiator = {
    attach: function (context) {
      once('views-table-client-side-download-csv-button-initialized', '[data-views-table-client-side-download-csv-button]', context).forEach(
        (btn) => {
          btn.addEventListener('click', buttonClickHandler);
        }
      );
    },
  };

})(Drupal, once);
