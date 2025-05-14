<?php

require_once("./include/php/functions.php");
require_once("./include/php/csv-utility-master/CsvUtility.php");

use csv\CsvUtility;
use csv\CsvWhere;

if (isset($_POST['status']))
{    
    $csvinfo = new CsvUtility("include/html/files/process_status_info.csv");

    $csvinfo->setField("process", \csv\CsvDataType::TEXT);

    $csvinfo->update([
        'status' => $_POST['status']
    ], [
        new CsvWhere("process", "=", "Processing Temp Data")
    ]);
}

echo 1; exit;