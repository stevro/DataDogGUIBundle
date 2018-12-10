/* 
 * This code belongs to NIMA Software SRL | nimasoftware.com
 * For details contact contact@nimasoftware.com
 */


function StevDataDogAuditGUI(jQuery) {

    if (!window.jQuery || !jQuery)
    {
        console.error('StevDataDogAuditGUI requires JQuery.');
        return;
    }

//    this.initAudit = function ()
//    {
//        $(document).on('click', '#btn-audit', function (e) {
//            initAjaxLoaderForPopUp();
//            var url = $('.entity_details.success').data("audit");
//            if (url == null) {
//                alertify.error("You must choose an order.");
//                return false;
//            }
//
//            $.ajax({
//                dataType: "html",
//                type: "GET",
//                url: url,
//                success: function (data, textStatus, jqXHR) {
//                    if (data) {
//                        $('.audit-info').html(data);
//                    }
//                    $('#audit-table').dataTable({
//                        "aaSorting": []
////                    stateSave: true
//                    });
//                    $('#audit').removeClass('loader');
//                    removeAjaxLoaderForPopUp();
//                },
//                error: function (jqXHR, textStatus, errorThrown) {
//                    $('.audit-info').html("");
//                    alertify.error("Something went wrong");
//                    removeAjaxLoaderForPopUp();
//                }
//            });
//            $('#audit').modal('show');
//            e.preventDefault();
//        });
//    }

//    function auditLogs()
//    {
//        $(document).on('click', '#btn-audit-logs', function (e) {
//            initAjaxLoaderForPopUp();
//            var entityClass = $('.entity_details.success').data("audit-entity-class");
//            var entityid = $('.entity_details.success').data("audit-entity-id");
//            var url = $('.entity_details.success').data("audit-url");
//            if (entityid == '') {
//                alertify.error("Nu puteti vedea jurnalul. Comanda nu are o factura generata.");
//                return false;
//            }
//            $.ajax({
//                dataType: "html",
//                type: "GET",
//                url: url,
//                data: {
//                    entityClass: entityClass,
//                    entityid: entityid,
//                },
//                success: function (data, textStatus, jqXHR) {
//                    if (data) {
//                        $('.audit-modal').remove();
//                        $(data).appendTo('.dashboard-wrapper');
//                        $('#audit').removeClass('loader');
//                        $('#audit').modal('show');
//                        $('#audit-table').dataTable();
//                    }
//
//                },
//                error: function (jqXHR, textStatus, errorThrown) {
//                    $('.audit-info').html("");
//                    alertify.error("Something went wrong");
//                }
//            });
//            e.preventDefault();
//        });
//    }

//    this.openLinkedAuditWindow = function ()
//    {
//        jQuery(document).on('click', '.assocDataAudit', function (e) {
////        initAjaxLoaderForPopUp();
//            var url = $(this).data("url");
//            if (url == null) {
//                alertify.error("You must choose an order.");
//                return false;
//            }
//
//            var fk = $(this).data("fk");
//            var table = $(this).data("tbl");
//            var modalId = 'modal-' + table + '-' + fk;
//            $.ajax({
//                dataType: "html",
//                type: "GET",
//                url: url,
//                data: {
//                    'fk': fk,
//                    'tbl': table,
//                    'entity': $(this).data("entity"),
//                    'includeInserts': 1
//                },
//                success: function (data, textStatus, jqXHR) {
//
//                    doModal(modalId, 'Audit ' + table, data, true);
//
//                    var datatableId = '#' + modalId + ' #audit-table';
//
//                    $(datatableId).dataTable({
////                    'order': [0, 'desc'],
////                    stateSave: true
//                    });
//                },
//                error: function (jqXHR, textStatus, errorThrown) {
//                    alertify.error("Something went wrong");
//                }
//            });
//            e.preventDefault();
//        });
//    }

    this.doModal = function (id, heading, content) {
        html = '<div id="' + id + '" class="modal fade inmodal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="confirm-modal" aria-hidden="true">';

        html += '<div class="modal-dialog modal-lg">';

        html += '<div class="modal-content">';
        html += '<div class="modal-header">';
        html += '<a class="close" data-dismiss="modal">×</a>';
        html += '<h4>' + heading + '</h4>'
        html += '</div>';
        html += '<div class="modal-body">';
        html += content;
        html += '</div>';
        html += '<div class="modal-footer text-center">';
        html += '<span class="btn btn-default" data-dismiss="modal">Close</span>';
        html += '</div>';  // content
        html += '</div>';  // dialog
        html += '</div>';  // footer
        html += '</div>';  // modalWindow

        jQuery('body').append(html);
        jQuery("#" + id).modal();
        jQuery("#" + id).modal('show');


        jQuery('#' + id).on('hidden.bs.modal', function (e) {

            jQuery(this).remove();
        });

    }

//    this.entityAuditLogs = function ()
//    {
//        jQuery(document).on('click', '#btn-audit-logs', function (e) {
//            initAjaxLoaderForPopUp();
//            var entityClass = $('.entity_details.success').data("audit-entity-class");
//            var entityid = $('.entity_details.success').data("audit-entity-id");
//            var url = $('.entity_details.success').data("audit-url");
//            var includeAssocs = $('.entity_details.success').data('include-assocs');
//
//            openAnEntityAuditLogs(entityid, entityClass, includeAssocs, url);
//
//            e.preventDefault();
//        });
//
//        jQuery(document).on('click', '.btn-audit-logs-self-contained', function (e) {
//            initAjaxLoaderForPopUp();
//            var entityClass = jQuery(this).data("audit-entity-class");
//            var entityid = jQuery(this).data("audit-entity-id");
//            var url = jQuery(this).data("audit-url");
//
//            var includeAssocs = $(this).data('include-assocs');
//
//            openAnEntityAuditLogs(entityid, entityClass, includeAssocs, url);
//
//            e.preventDefault();
//        });
//
//        this.openLinkedAuditWindow();
//    };

    this.openEntityAuditLogs = function (entityId, entityClass, includeAssocs = [], includeInserts = false) {
        if (!entityId || !entityClass) {

            console.error('You must provide an entity ID and the class name');

            return false;
        }

        var strToId = entityClass.replace(/[\s\\;,:]/g, "-");
        var modalId = 'modal-' + strToId + '-' + entityId;
        modalId = modalId.replace(/[\s]/g, '');

        console.log(entityId, entityClass, includeAssocs, includeInserts);

        this.getLogsForEntity(entityId, entityClass, includeAssocs, includeInserts)
                .done(function (data, textStatus, jqXHR) {
                    StevDataDogAuditGUI.doModal(modalId, 'Audit', data);

                    var datatableId = '#' + modalId + ' .audit-table';
                    $(datatableId).dataTable({
                        "order": [[0, "desc"]],
                        "ordering": false,
                    });
//                    var assocDatatableId = '#' + modalId + ' .audit-table-assocs';
//                    $(assocDatatableId).dataTable({
//                        "order": [[0, "desc"]],
//                        "ordering": false,
//                    });
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    alert("error");
                })
                .always(function () {
                });
    };

    this.getLogsForEntity = function (entityId, entityClass, dataType = 'html', includeAssocs = [], includeInserts = false) {
        return jQuery.ajax({
            dataType: dataType,
            type: "GET",
            url: Routing.generate('audit_entity_logs', {entityId: entityId, entityClass: entityClass}),
            data: {
                includeInserts: includeInserts,
                includeAssocs: includeAssocs
            },
        });
    };
}

var StevDataDogAuditGUI = new StevDataDogAuditGUI(window.jQuery);