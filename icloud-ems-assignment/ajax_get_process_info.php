<?php

use csv\CsvUtility;

require_once("./include/php/csv-utility-master/CsvUtility.php");

$csvinfo = new CsvUtility("include/html/files/process_status_info.csv");

$records = $csvinfo->find();

?>

<ul class="list-group">
    <?php foreach($records as $record): ?>
        <li class="list-group-item"><b><?= $record['process'] ?></b> : <span><?= $record['status'] ?></span></li>
    <?php endforeach; ?>
</ul>