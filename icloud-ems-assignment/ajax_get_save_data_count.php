<?php

use csv\CsvUtility;

require_once("./include/php/functions.php");
require_once("./include/php/DateUtility.php");
require_once("./include/php/csv-utility-master/CsvUtility.php");
require_once("./include/php/Mysql.php");

$mysql = new Mysql([
    "server" => "localhost",
    "username" => "root",
    "password" => "root",
    "database" => "icloud_ems",
]);

$csvinfo = new CsvUtility("include/html/files/process_status_info.csv");

$info_arr = $csvinfo->find();

$table_name = 'commonfeecollection';

$db_data = $mysql->select("SELECT COUNT(1) as c, sum(amount) as sum_amount FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$saved_sum = round($db_data[0]['sum_amount']);

$last_index_of_info_arr = count($info_arr);
$info_arr[$last_index_of_info_arr] = [
    "process" => "Common Fee Collection",
    "status" => "Count : $saved_count " . ($saved_count == 291383 ? " (Matched)" : "")
];

$info_arr[$last_index_of_info_arr]['status'] .= ", Sum : $saved_sum " . ($saved_sum == 11223471432 ? " (Matched)" : "");
$csvinfo->write($info_arr);



$table_name = 'commonfeecollectionheadwise';

$db_data = $mysql->select("SELECT COUNT(1) as c, sum(amount) as sum_amount FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$saved_sum = round($db_data[0]['sum_amount']);

$last_index_of_info_arr = count($info_arr);
$info_arr[$last_index_of_info_arr] = [
    "process" => "Common Fee Collection Head Wise",
    "status" => "Count : $saved_count " . ($saved_count == 412221 ? " (Matched)" : "")
];

$info_arr[$last_index_of_info_arr]['status'] .= ", Sum : $saved_sum " . ($saved_sum == 11223471432 ? " (Matched)" : "");
$csvinfo->write($info_arr);



$table_name = 'financialtran';

$db_data = $mysql->select("SELECT COUNT(1) as c, sum(amount) as sum_amount FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$saved_sum = round($db_data[0]['sum_amount']);

$last_index_of_info_arr = count($info_arr);
$info_arr[$last_index_of_info_arr] = [
    "process" => "financial ",
    "status" => "Count : $saved_count " . ($saved_count == 273142 ? " (Matched)" : "")
];

$info_arr[$last_index_of_info_arr]['status'] .= ", Sum : $saved_sum " . ($saved_sum == 14001686423 ? " (Matched)" : "");
$csvinfo->write($info_arr);



$table_name = 'financialtrandetail';

$db_data = $mysql->select("SELECT COUNT(1) as c, sum(amount) as sum_amount FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$saved_sum = round($db_data[0]['sum_amount']);

$last_index_of_info_arr = count($info_arr);
$info_arr[$last_index_of_info_arr] = [
    "process" => "financial detail",
    "status" => "Count : $saved_count " . ($saved_count == 472729 ? " (Matched)" : "")
];

$info_arr[$last_index_of_info_arr]['status'] .= ", Sum : $saved_sum " . ($saved_sum == 14001686423 ? " (Matched)" : "");
$csvinfo->write($info_arr);

echo 1; exit;