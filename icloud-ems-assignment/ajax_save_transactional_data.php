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


$csv_table_name = "temporary_completedata";

$offset = $_GET['offset'] ?? 0;
$limit = $_GET['limit'] ?? 1000;

$db_data = $mysql->select("SELECT COUNT(1) as c FROM `$csv_table_name`");
$total_temp_table_row_count = $db_data[0]['c'];

$total_temp_table_row_count = 50000;

$financial_id_list = $common_fee_id_list = [];

$query_status_info = [
    "single_insert" => 0,
    "multi_insert" => 0,
    "multi_query" => 0,
];

$db_data = $mysql->select("SELECT 
	ft.*,
    b.name as branch_name,
    fc.Fee_category as fee_category_name
FROM 
	feetypes ft
    INNER JOIN branches b on b.id = ft.Br_id
    INNER JOIN feecategory fc on fc.id = ft.Fee_category
    INNER JOIN feecollectiontype fct on fct.id = ft.Collection_id and fct.Collection_head='academic'");

// debug($db_data); exit;

$feetype_id_list = [];

foreach ($db_data as $db_arr) {
    $feetype_id_list[$db_arr['branch_name']][$db_arr['fee_category_name']][$db_arr['Fee_type_ledger']] = $db_arr;
}

// debug($feetype_id_list); exit;

$db_data = $mysql->select("SELECT * FROM entrymode");

$entry_mode_list = [];

foreach ($db_data as $db_arr) {
    $entry_mode_list[strtoupper(trim($db_arr['entry_modename']))] = $db_arr;
}

// debug($entry_mode_list); exit;

$q = "SELECT * from `$csv_table_name` LIMIT $limit OFFSET $offset";

$db_temp_records = $mysql->select($q);

if (!empty($db_temp_records)) 
{
    $raw_finance_data = $raw_common_fee_data =  [];

    foreach ($db_temp_records as $db_temp_record) {
        // debug($db_temp_record);  exit;
        foreach ($db_temp_record as $k => $v) {
            $db_temp_record[$k] = trim($v);
        }

        if (
            empty($db_temp_record['faculty'])
            || empty($db_temp_record['fee_category'])
            || empty($db_temp_record['fee_head'])
        ) {
            continue;
        }

        $fee_type = $feetype_id_list[$db_temp_record['faculty']][$db_temp_record['fee_category']][$db_temp_record['fee_head']] ?? null;

        if (is_null($fee_type))
        {
            continue;
        }
        // debug($fee_type); exit;

        $head_id = $fee_type['id'];
        $br_id = $fee_type['Br_id'];
        $fee_category_id = $fee_type['Fee_category'];
        $fee_collection_type_id = $fee_type['Collection_id'];
        $voucher_type = strtoupper(trim($db_temp_record["vocuher_type"]));
        $entry_mode = $entry_mode_list[$voucher_type]['id'] ?? 0;

        $db_temp_record['fee_category_id'] = $fee_category_id;
        $db_temp_record['fee_collection_type_id'] = $fee_collection_type_id;
        $db_temp_record['entry_mode'] = $entry_mode;

        $is_finance_table = in_array($voucher_type, ['DUE', 'REVDUE', 'SCHOLARSHIP', 'SCHOLARSHIPREV/REVCONCESSION', 'CONCESSION', 'WRITEOFF']);

        $db_temp_record['type_of_concession'] = $voucher_type == "CONCESSION" ? 1 : ($voucher_type == "SCHOLARSHIP" ? 2 : 0);

        $cr_dr = "D";
        switch ($voucher_type) {
            case "CONCESSION":
            case "SCHOLARSHIP":
            case "REVDUE":
            case "RCPT":
            case "JV":
            case "REVPMT":
                $cr_dr = "C";
                break;

            case "Fundtransfer":
                $cr_dr = "+ ve and -ve";
                break;
        }

        $in_active = 0;
        switch ($voucher_type) {
            case "REVRCPT":
            case "REVJV":
            case "REVPMT":
                $in_active = 1;
                break;

            case "FUNDTRANSFER":
                $in_active = null;
                break;
        }


        $db_temp_record['in_active'] = $in_active;

        if ($is_finance_table) {
            $raw_finance_data[$br_id][$db_temp_record['admission_no']][$db_temp_record['date']][$cr_dr][$head_id][] = $db_temp_record;
        } else {
            $raw_common_fee_data[$br_id][$db_temp_record['admission_no']][$db_temp_record['date']][$db_temp_record['reciept_no']][$head_id][] = $db_temp_record;
        }
    }

    unset($db_temp_records);


    // debug($raw_finance_data); exit;

    $table_name = 'financialtran';
    $table_name2 = 'financialtrandetail';

    $finance_records = [];
    foreach ($raw_finance_data as $br_id => $branch_records) {
        foreach ($branch_records as $admission_no => $raw_finance_admission_records) {
            foreach ($raw_finance_admission_records as $date => $raw_finance_date_records) {
                foreach ($raw_finance_date_records as $cr_dr => $raw_finance_cr_dr_record) {
                    $finance_record = [
                        "moduleid" => 1,
                        "transid" => rand(1, 100000000),
                        "admno" => $admission_no,
                        "amount" => 0,
                        "crdr" => $cr_dr,
                        "tranDate" => $date,
                        "brid" => $br_id,
                        "children" => []
                    ];

                    foreach ($raw_finance_cr_dr_record as $head_id => $raw_finance_head_records) {
                        foreach ($raw_finance_head_records as $raw_finance_head_record) {
                            // debug($raw_finance_head_record); exit;
                            $finance_record['moduleid'] = 1;
                            $finance_record['acadYear'] = $raw_finance_head_record['academic_year'];
                            $finance_record['Entrymode'] = $raw_finance_head_record['entry_mode'];
                            $finance_record['voucherno'] = $raw_finance_head_record['voucher_no'];
                            $finance_record['type_of_concession'] = $raw_finance_head_record['type_of_concession'];

                            $amount = $raw_finance_head_record['due_amount'];
                            $amount += $raw_finance_head_record['write_off_amount'];
                            $amount += $raw_finance_head_record['scholarship_amount'];
                            $amount += $raw_finance_head_record['reverse_concession_amount'];
                            $amount += $raw_finance_head_record['concession_amount'];

                            $finance_record['children'][] = [
                                "financialTranId" => null,
                                "moduleId" => 1,
                                "amount" => $amount,
                                "headId" => $head_id,
                                "crdr" => $cr_dr,
                                "brid" => $br_id,
                                "head_name" => $raw_finance_head_record['fee_head'],
                            ];
                        }
                    }

                    foreach ($finance_record['children'] as $children) {
                        $finance_record['amount'] += $children['amount'];
                    }

                    $finance_records[] = $finance_record;
                }
            }
        }
    }

    unset($raw_finance_data);

    $key_found = false;
    foreach ($finance_records as $finance_record) {
        $save = $finance_record;
        unset($save['children']);

        $key = $finance_record['brid'] . '~' . $finance_record['admno'] . '~' . $finance_record['voucherno'] . '~' .  $finance_record['type_of_concession'];
        if (isset($financial_id_list[$key])) {
            $key_found = true;
        }
    }

    if ($key_found) {
        foreach ($finance_records as $finance_record) {
            $save = $finance_record;
            unset($save['children']);

            $key = $finance_record['brid'] . '~' . $finance_record['admno'] . '~' . $finance_record['voucherno'] . '~' .  $finance_record['type_of_concession'];
            if (!isset($financial_id_list[$key])) {
                $financial_id_list[$key] = $mysql->save($table_name, $save);
                $query_status_info['single_insert']++;
            }

            foreach ($finance_record['children'] as $k => $children) {
                $finance_record['children'][$k]['financialTranId'] = $financial_id_list[$key];
            }

            $mysql->insertMany($table_name2, $finance_record['children']);
            $query_status_info['multi_insert']++;
        }
    } else {
        $query = "";
        $finance_record_count = count($finance_records);
        foreach ($finance_records as $j => $finance_record) {
            $save = $finance_record;
            unset($save['children']);

            $query .= $mysql->getInsertQuery($table_name, $save);

            $query .= "SET @last_id = LAST_INSERT_ID();";

            foreach ($finance_record['children'] as $k => $children) {
                $finance_record['children'][$k]['financialTranId'] = '@last_id';
            }

            $query .= $mysql->getInsertManyQuery($table_name2, $finance_record['children']);

            if (strlen($query) > 1024 * 1024 * 4 || $j  == $finance_record_count - 1) {
                $mysql->multi_query($query);
                $query_status_info['multi_query']++;
            }
        }
    }

    $table_name = 'commonfeecollection';
    $table_name2 = 'commonfeecollectionheadwise';

    $common_fee_records = [];
    foreach ($raw_common_fee_data as $br_id => $branch_records) {
        foreach ($branch_records as $admission_no => $raw_admission_records) {
            foreach ($raw_admission_records as $date => $raw_date_records) {
                foreach ($raw_date_records as $reciept_no => $raw_reciept_no_records) {
                    $record = [
                        "moduleid" => 1,
                        "transid" => rand(1, 100000000),
                        "admno" => $admission_no,
                        "rollno" => "",
                        "amount" => 0,
                        "brId" => $br_id,
                        "acadamicYear" => "",
                        "financialYear" => "",
                        "displayReceiptNo" => $reciept_no,
                        "entrymode" => "",
                        "paid_date" => 0,
                        "inactive" => 0,
                        "children" => []
                    ];

                    foreach ($raw_reciept_no_records as $head_id => $raw_head_records) {
                        foreach ($raw_head_records as $raw_head_record) {
                            // debug($raw_head_record); exit;
                            $finance_record['moduleid'] = 1;
                            $record['rollno'] = $raw_head_record['roll_no'];
                            $record['acadamicYear'] = $raw_head_record['academic_year'];
                            $record['entrymode'] = $raw_head_record['entry_mode'];
                            $record['inactive'] = $raw_head_record['in_active'];

                            $amount = $raw_head_record['paid_amount'];
                            $amount += $raw_head_record['adjusted_amount'];
                            $amount += $raw_head_record['refund_amount'];
                            $amount += $raw_head_record['fund_transfer_amount'];

                            $record['children'][] = [
                                "moduleId" => 1,
                                "receiptId" => null,
                                "headId" => $head_id,
                                "headName" => $raw_head_record['fee_head'],
                                "brid" => $br_id,
                                "amount" => $amount,
                            ];
                        }
                    }

                    foreach ($record['children'] as $children) {
                        $record['amount'] += $children['amount'];
                    }

                    $common_fee_records[] = $record;
                }
            }
        }
    }

    unset($raw_common_fee_data);

    $key_found = false;
    foreach ($common_fee_records as $common_fee_record) {
        $save = $common_fee_record;
        unset($save['children']);

        $key = $common_fee_record['brId'] . '~' . $common_fee_record['admno'] . '~' . $common_fee_record['rollno'] . '~' . $common_fee_record['displayReceiptNo'] . '~' . $common_fee_record['entrymode'] . '~' . $common_fee_record['inactive'];
        if (isset($common_fee_id_list[$key])) {
            $key_found = true;
        }
    }

    if ($key_found) {
        foreach ($common_fee_records as $common_fee_record) {
            $save = $common_fee_record;
            unset($save['children']);

            $key = $common_fee_record['brId'] . '~' . $common_fee_record['admno'] . '~' . $common_fee_record['rollno'] . '~' . $common_fee_record['displayReceiptNo'] . '~' . $common_fee_record['entrymode'] . '~' . $common_fee_record['inactive'];
            if (!isset($common_fee_id_list[$key])) {
                $common_fee_id_list[$key] = $mysql->save($table_name, $save);
                $query_status_info['single_insert']++;
            }

            foreach ($common_fee_record['children'] as $k => $children) {
                $common_fee_record['children'][$k]['receiptId'] = $common_fee_id_list[$key];
            }

            $mysql->insertMany($table_name2, $common_fee_record['children']);
            $query_status_info['multi_insert']++;
        }
    } else {
        $query = "";
        $common_fee_record_count = count($common_fee_records);
        foreach ($common_fee_records as $j => $common_fee_record) {
            $save = $common_fee_record;
            unset($save['children']);

            $query .= $mysql->getInsertQuery($table_name, $save);

            $query .= "SET @last_id = LAST_INSERT_ID();";

            foreach ($common_fee_record['children'] as $k => $children) {
                $common_fee_record['children'][$k]['receiptId'] = '@last_id';
            }

            $query .= $mysql->getInsertManyQuery($table_name2, $common_fee_record['children']);

            if (strlen($query) > 1024 * 1024 * 4 || $j  == $common_fee_record_count - 1) {
                $mysql->multi_query($query);
                $query_status_info['multi_query']++;
            }
        }
    }
}


$ret = [
    "offset" => $offset,
    "query_status_info" => $query_status_info
];

echo json_encode($ret);
exit;
