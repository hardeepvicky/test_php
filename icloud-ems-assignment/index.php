<?php
require_once("./include/php/functions.php");
?>

<html>

<head>
    <title>iCloud Ems</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" type="text/css" href="include/html/bootstrap-5.3.3-dist/css/bootstrap.min.css" />

    <script type="text/javascript" src="include/html/js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="include/html/js/jquery.form.min.js"></script>
</head>

<body>

    <div class="container">
        <nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">iCloud EMS Assignment</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </nav>

        <div class="card mt-2">
            <div class="card-body">
                <form id="upload" method="POST" action="ajax_upload_file.php">
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input name="file" type="file" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mt-2" id="process_block" style="display: none;">
            <div class="card-body">
                <label class="form-label" id="uploading_status">Uploading</label>
                <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
                </div>

                <div class="mt-2" id="after_upload" style="display: none;">
                    <button id="trigger_import_csv" type="button" class="btn btn-primary">Start Import CSV</button>
                    <button id="trigger_distribute_temp_data_to_tables" type="button" class="btn btn-primary" style="display: none;">Start Distribute Temporary Table Data To Another Tables</button>

                    <div id="spinner" class="spinner-border" role="status" style="display: none;">
                        <span class="visually-hidden">Loading...</span>
                    </div>

                    <div id="process_csv_status" class="mt-2"></div>

                    <div id="process_csv_error" class="mt-2 text-danger"></div>
                </div>
            </div>
        </div>

    </div>

    <script type="text/javascript" src="include/html/bootstrap-5.3.3-dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="include/html/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var file = null;
        var interval_loop = null, interval_loop_of_send_processing_status = null;
        var group_queue_list = [];
        var limit = 5000;
        var total_record_count = 0;
        var process_record_count = 0;
        var update_processing_status_is_running = false;
        var process_info_is_running = false;

        function before_ajax() {
            $("#spinner").show();
            $("#trigger_import_csv").hide();
            $("#trigger_distribute_temp_data_to_tables").hide();
            $("#process_csv_error").html('');
        }

        function after_ajax() {
            setTimeout(function() {
                clearInterval(interval_loop);
                clearInterval(interval_loop_of_send_processing_status);
            }, 2000);
            $("#spinner").hide();
            $("#trigger_import_csv").show();
            $("#trigger_distribute_temp_data_to_tables").show();
        }

        function get_process_info()
        {
            interval_loop = setInterval(function() {
                if (process_info_is_running == false)
                {
                    process_info_is_running = true;
                    $("#process_csv_status").load("ajax_get_process_info.php", function(){
                        process_info_is_running = false;
                    });
                }
            }, 2000);
        }
        function start_processing() {
            $.get("ajax_distribute_temp_table_data_to_another_tables.php", function(response) {

                try {
                    response = JSON.parse(response);                    
                    start_transaction_data_save(response['total_csv_count']);
                } catch (e) {
                    $("#process_csv_error").html(e.message);
                    $("#process_csv_error").append(response);
                    after_ajax();
                }

            });
        }

        function update_processing_status()
        {
            interval_loop_of_send_processing_status = setInterval(function (){
                var per = process_record_count * 100 / total_record_count;
                per = per.toFixed(2);
                
                if (update_processing_status_is_running == false)
                {
                    update_processing_status_is_running = true;
                    $.post(`ajax_save_processing_temp_data_status.php`, {status : per + " %"}, function(response) {
                        update_processing_status_is_running = false;
                        if (response != "1") {
                            $("#process_csv_error").append(response);
                            return;
                        }
                    });
                }
                
            }, 1000);
        }

        function start_transaction_data_save(total_count) {

            total_record_count = total_count;
            process_record_count = 0;

            var total_pages = Math.ceil(total_record_count / limit);

            var queue_list = [];
            for (var i = 0; i < total_pages; i++) {
                var offset = i * limit;
                queue_list.push(offset);
                if (i > 0 && i % 20 == 0 || i == total_pages - 1 && queue_list.length > 0) {
                    group_queue_list.push(queue_list);
                    queue_list = [];
                }
            }

            console.log("group_queue_list", group_queue_list);
            save_transactional_data(0);
            update_processing_status();
        }

        function save_transactional_data(queue_index) {

            console.log("starting queue", queue_index);
            if (queue_index >= group_queue_list.length) {
                console.log("queue finished");
                get_save_counts();
                return;
            }

            var queue_list = group_queue_list[queue_index];

            console.log("queue_list", queue_list);

            for (var i in queue_list) {
                var offset = queue_list[i];

                $.get(`ajax_save_transactional_data.php?offset=${offset}&limit=${limit}`, function(response) {

                    try {
                        response = JSON.parse(response);
                        console.log(response);
                        if (typeof response['offset'] != "undefined") {

                            var index = queue_list.indexOf(parseInt(response['offset']));
                            if (index !== -1) {
                                queue_list.splice(index, 1);
                            }

                            console.log({
                                "index": index,
                                "queue_list": queue_list
                            });

                            process_record_count += limit;

                            if (queue_list.length == 0) {
                                save_transactional_data(queue_index + 1);
                            }
                        }

                    } catch (e) {
                        $("#process_csv_error").html(e.message);
                        $("#process_csv_error").append(response);
                    }


                });
            }
        }

        function get_save_counts() {
            $.get(`ajax_get_save_data_count.php`, function(response) {

                if (response != "1") {
                    $("#process_csv_error").append(response);
                    return;
                }

                after_ajax();
            });
        }


        $(function() {

            $("form#upload").ajaxForm({
                beforeSubmit: function(arr, $form, options) {
                    $("#process_block").show();
                    return true;
                },
                uploadProgress: function(e, position, total, per) {
                    $("#progress-bar").css("width", per + "%");
                    $("#uploading_status").html("Uploading : " + per + " %");
                },
                complete: function(xhr) {
                    var response = xhr.responseText;
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error(response);
                        return;
                    }

                    console.log(response);
                    file = response['file'];
                    $("#after_upload").show();
                    $("#trigger_import_csv").trigger("click");

                }
            });


            $("#trigger_import_csv").click(function() {

                before_ajax();

                get_process_info();

                $.post("ajax_import_csv.php", {
                    file: file
                }, function(response) {

                    if (response != "1") {
                        $("#process_csv_error").html(response);
                    }

                    after_ajax();
                });
            });

            $("#trigger_distribute_temp_data_to_tables").click(function() {

                before_ajax();
                
                get_process_info();

                start_processing();
            });


        });
    </script>
</body>

</html>