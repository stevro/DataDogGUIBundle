/* 
 * This code belongs to NIMA Software SRL | nimasoftware.com
 * For details contact contact@nimasoftware.com
 */


function initAudit()
{
    $(document).on('click', '#btn-audit', function (e) {
        initAjaxLoaderForPopUp();
        var url = $('.entity_details.success').data("audit");
        if (url == null) {
            alertify.error("You must choose an order.");
            return false;
        }

        $.ajax({
            dataType: "html",
            type: "GET",
            url: url,
            success: function (data, textStatus, jqXHR) {
                if (data) {
                    $('.audit-info').html(data);
                }
                $('#audit-table').dataTable({
                    "aaSorting": []
//                    stateSave: true
                });
                $('#audit').removeClass('loader');
                removeAjaxLoaderForPopUp();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $('.audit-info').html("");
                alertify.error("Something went wrong");
                removeAjaxLoaderForPopUp();
            }
        });
        $('#audit').modal('show');
        e.preventDefault();
    });
}

function auditLogs()
{
    $(document).on('click', '#btn-audit-logs', function (e) {
        initAjaxLoaderForPopUp();
        var entityClass = $('.entity_details.success').data("audit-entity-class");
        var entityid = $('.entity_details.success').data("audit-entity-id");
        var url = $('.entity_details.success').data("audit-url");
        if (entityid == '') {
            alertify.error("Nu puteti vedea jurnalul. Comanda nu are o factura generata.");
            return false;
        }
        $.ajax({
            dataType: "html",
            type: "GET",
            url: url,
            data: {
                entityClass: entityClass,
                entityid: entityid,
            },
            success: function (data, textStatus, jqXHR) {
                if (data) {
                    $('.audit-modal').remove();
                    $(data).appendTo('.dashboard-wrapper');
                    $('#audit').removeClass('loader');
                    $('#audit').modal('show');
                    $('#audit-table').dataTable();
                }

            },
            error: function (jqXHR, textStatus, errorThrown) {
                $('.audit-info').html("");
                alertify.error("Something went wrong");
            }
        });
        e.preventDefault();
    });
}

function openLinkedAuditWindow()
{
    $(document).on('click', '.assocDataAudit', function (e) {
//        initAjaxLoaderForPopUp();
        var url = $(this).data("url");
        if (url == null) {
            alertify.error("You must choose an order.");
            return false;
        }

        var fk = $(this).data("fk");
        var table = $(this).data("tbl");
        var modalId = 'modal-' + table + '-' + fk;
        $.ajax({
            dataType: "html",
            type: "GET",
            url: url,
            data: {
                'fk': fk,
                'tbl': table,
                'entity': $(this).data("entity"),
                'includeInserts': 1
            },
            success: function (data, textStatus, jqXHR) {

                doModal(modalId, 'Audit ' + table, data, true);

                var datatableId = '#' + modalId + ' #audit-table';

                $(datatableId).dataTable({
//                    'order': [0, 'desc'],
//                    stateSave: true
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alertify.error("Something went wrong");
            }
        });
        e.preventDefault();
    });
}

function doModal(id, heading, formContent, hasWidth) {
    html = '<div id="' + id + '" class="modal fade audit-modal" data-backdrop="false" tabindex="-1" role="dialog" aria-labelledby="confirm-modal" aria-hidden="true">';

    if (hasWidth) {
        html += '<div class="modal-dialog hasWidth">';
    } else {
        html += '<div class="modal-dialog">';
    }
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<a class="close" data-dismiss="modal">×</a>';
    html += '<h4>' + heading + '</h4>'
    html += '</div>';
    html += '<div class="modal-body">';
    html += formContent;
    html += '</div>';
    html += '<div class="modal-footer text-center">';
    html += '<span class="btn btn-success" data-dismiss="modal">Close</span>';
    html += '</div>';  // content
    html += '</div>';  // dialog
    html += '</div>';  // footer
    html += '</div>';  // modalWindow
    $('body').append(html);
    $("#" + id).modal();
    $("#" + id).modal('show');


    $('#' + id).on('hidden.bs.modal', function (e) {

        $(this).remove();
    });

}

function entityAuditLogs()
{
    $(document).on('click', '#btn-audit-logs', function (e) {
        initAjaxLoaderForPopUp();
        var entityClass = $('.entity_details.success').data("audit-entity-class");
        var entityid = $('.entity_details.success').data("audit-entity-id");
        var url = $('.entity_details.success').data("audit-url");
        var includeAssocs = $('.entity_details.success').data('include-assocs');

        openAnEntityAuditLogs(entityid, entityClass, includeAssocs, url);

        e.preventDefault();
    });

    $(document).on('click', '.btn-audit-logs-self-contained', function (e) {
        initAjaxLoaderForPopUp();
        var entityClass = $(this).data("audit-entity-class");
        var entityid = $(this).data("audit-entity-id");
        var url = $(this).data("audit-url");

        var includeAssocs = $(this).data('include-assocs');

        openAnEntityAuditLogs(entityid, entityClass, includeAssocs, url);

        e.preventDefault();
    });

    openLinkedAuditWindow();
}

function openAnEntityAuditLogs(entityid, entityClass, includeAssocs, url) {
    if (entityid == '') {
        var errMsg = $('.entity_details.success').data('audit-invalidid-msg');
        alertify.error(errMsg);
        return false;
    }

    var strToId = entityClass.replace(/\\/g, "-");
    var modalId = 'modal-' + strToId + '-' + entityid;
    modalId = modalId.replace(/\s/g, '');
    console.log(entityid, entityClass, includeAssocs, url);
    $.ajax({
        dataType: "html",
        type: "GET",
        url: url,
        data: {
            entity: entityClass,
            fk: entityid,
            'includeInserts': 0,
            includeAssocs: includeAssocs
        },
        success: function (data, textStatus, jqXHR) {
            doModal(modalId, 'Audit', data, true);

            var datatableId = '#' + modalId + ' #audit-table';
            $(datatableId).dataTable({});
            var assocDatatableId = '#' + modalId + ' .audit-table-assocs';
            $(assocDatatableId).dataTable({
                "order": [[0, "desc"]],
                "ordering": false,
            });
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $('.audit-info').html("");
            alertify.error("Something went wrong");
        }
    });
}
