<?php

ini_set('memory_limit', '1G');
set_time_limit(3600);

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

$csv_table_name = "temporary_completedata";

/////////////////////////////////////////////////////////////////////////////////////////

$last_index_of_info_arr = count($info_arr);

$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Branches",
    "status" => "..."
];

$csvinfo->write($info_arr);

$q = "SELECT DISTINCT faculty from `$csv_table_name`";

$db_data = $mysql->select($q);

$table_name = 'branches';

$mysql->query("TRUNCATE TABLE `$table_name`");

$branches = [];
foreach($db_data as $db_row)
{
    $db_row['faculty'] = trim($db_row['faculty']);
    if ($db_row['faculty'])
    {
        $q = "INSERT INTO `$table_name`(name)values('" . $db_row['faculty'] . "')";
        $mysql->query($q);
        $branches[$db_row['faculty']] = $mysql->getLastInsertId();
    }
}

// debug($branches);

$db_data = $mysql->select("SELECT COUNT(1) as c FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Branches",
    "status" => "$saved_count Rows Saved"
];

$csvinfo->write($info_arr);


////////////////////////////////////////////////////////////////////////////////////////////

$last_index_of_info_arr = count($info_arr);

$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Fee Category",
    "status" => "..."
];

$csvinfo->write($info_arr);

$table_name = 'feecategory';

$q = "SELECT DISTINCT faculty, fee_category from `$csv_table_name`";

$db_data = $mysql->select($q);

$mysql->query("TRUNCATE TABLE `$table_name`");

$fee_categories = [];

foreach($db_data as $db_row)
{
    $db_row['faculty'] = trim($db_row['faculty']);
    $db_row['fee_category'] = trim($db_row['fee_category']);
    if ($db_row['faculty'] && $db_row['fee_category'])
    {
        $br_id = $branches[$db_row['faculty']];
        $q = "INSERT INTO `$table_name`(`Fee_category`, `Br_id`)values('" . $db_row['fee_category'] . "', $br_id)";
        $mysql->query($q);

        $fee_categories[$br_id][$db_row['fee_category']] = $mysql->getLastInsertId();
    }
}

// debug($fee_categories);

$db_data = $mysql->select("SELECT COUNT(1) as c FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Fee Category",
    "status" => "$saved_count Rows Saved"
];
$csvinfo->write($info_arr);

////////////////////////////////////////////////////////////////////////////////////////////

$last_index_of_info_arr = count($info_arr);

$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Fee Collection Type",
    "status" => "..."
];

$csvinfo->write($info_arr);

$table_name = 'feecollectiontype';

$q = "SELECT id, module_name from `module`";

$module_data = $mysql->select($q);

$mysql->query("TRUNCATE TABLE `$table_name`");

$fee_collection_types = [];

// debug($module_data);
foreach($branches as $branch => $br_id)
{
    foreach($module_data as $module_row)
    {
        $q = "INSERT INTO `$table_name`(`Collection_head`, `Collection_desc`, `Br_id`)
                values('" . $module_row['module_name'] . "', '" . $module_row['module_name'] . "', $br_id)";
        $mysql->query($q);

        $fee_collection_types[$br_id][$module_row['module_name']] = $mysql->getLastInsertId();
    }
}

// debug($fee_collection_types);


$db_data = $mysql->select("SELECT COUNT(1) as c FROM `$table_name`");
$saved_count = $db_data[0]['c'];
$info_arr[$last_index_of_info_arr] = [
    "process" => "Saveing Fee Collection Type",
    "status" => "$saved_count Rows Saved"
];
$csvinfo->write($info_arr);


/////////////////////////////////////////////////////////////////////////////////////////

$db_data = $mysql->select("SELECT COUNT(1) as c FROM `$csv_table_name`");
$total_temp_table_row_count = $db_data[0]['c'];

$q = "SELECT * FROM entrymode";
$temp = $mysql->select($q);

$entry_modes = [];
foreach($temp as $arr)
{
    $entry_modes[$arr['entry_modename']] = $arr;
}

$feetype_data = [];

$mysql->query("TRUNCATE TABLE `feetypes`");

$table_name = 'financialtran';
$mysql->query("TRUNCATE TABLE `$table_name`");

$table_name = 'financialtrandetail';
$mysql->query("TRUNCATE TABLE `$table_name`");

$table_name = 'commonfeecollection';
$mysql->query("TRUNCATE TABLE `$table_name`");

$table_name = 'commonfeecollectionheadwise';
$mysql->query("TRUNCATE TABLE `$table_name`");

$offset = 0;
$limit = 10000;
// $total_temp_table_row_count = 10000;

$last_index_of_info_arr = count($info_arr);

$info_arr[$last_index_of_info_arr] = [
    "process" => "Saving feetypes",
    "status" => "..."
];

$csvinfo->write($info_arr);

$fee_type_records = [];

while($offset <= $total_temp_table_row_count)
{
    $q = "SELECT * from `$csv_table_name` LIMIT $limit OFFSET $offset";

    $db_temp_records = $mysql->select($q);

    if (empty($db_temp_records))
    {
        $info_arr[$last_index_of_info_arr] = [
            "process" => "Saving feetypes",
            "status" =>  "100%"
        ];

        $csvinfo->write($info_arr);

        break;
    }

    $info_arr[$last_index_of_info_arr] = [
        "process" => "Saving feetypes",
        "status" => "Loop offset $offset"
    ];
    $csvinfo->write($info_arr);

    $offset += $limit;

    $raw_finance_data = $raw_common_fee_data =  [];

    foreach($db_temp_records as $db_temp_record)
    {
        foreach($db_temp_record as $k => $v)
        {
            $db_temp_record[$k] = trim($v);
        }

        $br_id = $fee_category_id = null;

        if ($db_temp_record['faculty'])
        {
            $br_id = $branches[$db_temp_record['faculty']];
        }

        if (!$br_id)
        {
            continue;
        }

        if ($db_temp_record['fee_category'])
        {
            $fee_category_id = $fee_categories[$br_id][$db_temp_record['fee_category']] ?? null;
        }

        if (!$fee_category_id)
        {
            continue;
        }

        foreach($module_data as $module_row)
        {
            $fee_collection_type_id = $fee_collection_types[$br_id][$module_row['module_name']] ?? null;

            if ($fee_collection_type_id)
            {
                $feetype_data[$br_id][$fee_category_id][$fee_collection_type_id][$db_temp_record['fee_head']]['module_id'] = $module_row['id'];
            }
        }
    }

    unset($db_temp_records);


    foreach($feetype_data as $br_id => $fee_category_records)
    {
        foreach($fee_category_records as $fee_category_id => $fee_collection_records)
        {
            foreach($fee_collection_records as $fee_collection_type_id => $fee_head_records)
            {
                foreach($fee_head_records as $fee_head => $module)
                {
                    $module_id = $module['module_id'];

                    $save = [
                        'Fee_category' => $fee_category_id,
                        'F_name' => $fee_head,
                        'Collection_id' => $fee_collection_type_id,
                        'Br_id' => $br_id,
                        'Seq_id' => $br_id,
                        'Fee_type_ledger' => $fee_head,
                        "Fee_headtype" => $module_id
                    ];

                    $fee_type_records[] = $save;

                }
            }
        }
    }

}

$query = "";
$fee_type_record_count = count($fee_type_records);

$query = $mysql->getInsertManyQuery('feetypes', $fee_type_records);

// debug($query); exit;

$mysql->multi_query($query);


$db_data = $mysql->select("SELECT COUNT(1) as c FROM `feetypes`");
$saved_count = $db_data[0]['c'];
$info_arr[$last_index_of_info_arr] = [
    "process" => "Saving Fee Collection Type",
    "status" => "$saved_count Rows Saved"
];
$csvinfo->write($info_arr);


$$last_index_of_info_arr = count($info_arr);
$info_arr[$last_index_of_info_arr] = [
    "process" => "Processing Temp Data",
    "status" => '....'
];
$csvinfo->write($info_arr);

$ret = [
    "total_csv_count" => $total_temp_table_row_count
];

echo json_encode($ret); exit;