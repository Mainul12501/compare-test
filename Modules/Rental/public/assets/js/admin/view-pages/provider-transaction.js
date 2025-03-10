"use strict";
// Call the dataTables jQuery plugin
$(document).ready(function () {
    $('#dataTable').DataTable();
    // INITIALIZATION OF DATATABLES
    // =======================================================
    let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

    $('#column1_search').on('keyup', function () {
        datatable
            .columns(1)
            .search(this.value)
            .draw();
    });

    $('#column2_search').on('keyup', function () {
        datatable
            .columns(2)
            .search(this.value)
            .draw();
    });

    $('#column3_search').on('change', function () {
        datatable
            .columns(3)
            .search(this.value)
            .draw();
    });

    $('#column4_search').on('keyup', function () {
        datatable
            .columns(4)
            .search(this.value)
            .draw();
    });


    // INITIALIZATION OF SELECT2
    // =======================================================
    $('.js-select2-custom').each(function () {
        let select2 = $.HSCore.components.HSSelect2.init($(this));
    });
});
