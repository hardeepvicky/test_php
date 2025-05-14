<?php

use csv\CsvUtility;
use csv\CsvWhere;

ini_set('memory_limit', '4G');
set_time_limit(3600);

$file = $_POST['file'];

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

$csv = new CsvUtility($file);

$csvinfo = new CsvUtility("include/html/files/process_status_info.csv");

$info_arr = [
    [
        "process" => "Getting Data From CSV",
        "status" => "",
    ]
];

$csvinfo->write($info_arr);

$csv_records = $csv->find([], [], null, null, 6);

// debug($csv_records); exit;

$total_count = count($csv_records);

$info_arr = [
    [
        "process" => "Getting Data From CSV",
        "status" => "Done",
    ],
    [
        "process" => "Total Count",
        "status" => $total_count,
    ]
];

$csvinfo->write($info_arr);

$csv_map_fields = [
    "Date" => "date",
    "Academic Year" => "academic_year",
    "Session" => "session",
    "Alloted Category" => "alloted_category",
    "Voucher Type" => "vocuher_type",
    "Voucher No." => "voucher_no",
    "Roll No." => "roll_no",
    "Admno/UniqueId" => "admission_no",
    "Status" => "status",
    "Fee Category" => "fee_category",
    "Faculty" => "faculty",
    "Program" => "program",
    "Department" => "department",
    "Batch" => "batch",
    "Receipt No." => "reciept_no",
    "Fee Head" => "fee_head",
    "Due Amount" => "due_amount",
    "Paid Amount" => "paid_amount",
    "Concession Amount" => "concession_amount",
    "Scholarship Amount" => "scholarship_amount",
    "Reverse Concession Amount" => "reverse_concession_amount",
    "Write Off Amount" => "write_off_amount",
    "Adjusted Amount" => "adjusted_amount",
    "Refund Amount" => "refund_amount",
    "Fund TranCfer Amount" => "fund_transfer_amount",
    "Remarks" => "remarks",
];

$rows = [];

$mysql->query("TRUNCATE TABLE `temporary_completedata`");

$info_arr[2] = [
    "process" => "Saving CSV data to Temprary Table",
    "status" => "Processsing....",
];

$csvinfo->write($info_arr);

$p_count = 0;

foreach ($csv_records as $index => $csv_record) {
    $row = [];

    foreach ($csv_map_fields as $csv_field => $db_field) {
        if (isset($csv_record[$csv_field])) {
            $row[$db_field] = $csv_record[$csv_field];
        }
    }

    if (empty($row))
    {
        continue;
    }

    list($d, $m, $y) = explode(",", $row['date']);

    $row['date'] = (date_create("$y-$m-$d"))->format("Y-m-d");

    // debug($row); exit;
    $rows[] = $row;

    if (count($rows) >= 500 || $index == $total_count - 1) {

        try
        {
            $mysql->insertMany("temporary_completedata", $rows);
            
            $p_count += count($rows);

            $per = round($p_count * 100 / $total_count, 1);
            
            $info_arr[2] = [
                "process" => "Saving CSV data to Temp. Table",
                "status" => "$per %",
            ];

            $csvinfo->write($info_arr);
        }
        catch(Exception $ex)
        {
            debug($rows);
            throw $ex;
        }

        $rows = [];
    }
}

$db_data = $mysql->select("
    SELECT 
        SUM(due_amount) as sum_due_amount,  
        SUM(paid_amount) as sum_paid_amount,  
        SUM(concession_amount) as sum_concession_amount,  
        SUM(scholarship_amount ) as sum_scholarship_amount,  
        SUM(refund_amount ) as sum_refund_amount
    FROM 
        `temporary_completedata`
");

$db_data = $db_data[0];

$sum = round($db_data['sum_due_amount']);

$info_arr[] = [
    "process" => "Sum Of Due Amount",
    "status" => $sum . ", " . ($sum == 12654422921 ? "(Match)" : ""),
];


$sum = round($db_data['sum_paid_amount']);
$info_arr[] = [
    "process" => "Sum Of paid Amount",
    "status" => $sum . ", " . ($sum == 11461021901 ? "(Match)" : ""),
];

$sum = round($db_data['sum_concession_amount']);
$info_arr[] = [
    "process" => "Sum Of Concesssion Amount",
    "status" => $sum . ", " . ($sum == 90544480 ? "(Match)" : ""),
];

$sum = round($db_data['sum_scholarship_amount']);
$info_arr[] = [
    "process" => "Sum Of Scholarship Amount",
    "status" => $sum . ", " . ($sum == 471818093 ? "(Match)" : ""),

];

$sum = round($db_data['sum_refund_amount']);
$info_arr[] = [
    "process" => "Sum Of Refund Amount",
    "status" => $sum . ", " . ($sum == -173381473 ? "(Match)" : ""),

];

$csvinfo->write($info_arr);

echo 1;
exit;
