<?php
require_once("./include/php/FileUtility.php");
$response = [];

$fileUtility = new FileUtility(1024 * 1024 * 1000);

if ($fileUtility->uploadFile($_FILES['file'], "public/")) 
{
    $response["file"] = $fileUtility->path . $fileUtility->file;
    $response["filename"] = $fileUtility->filename;
    $response["ext"] = strtolower($fileUtility->extension);
    $response["size_in_mb"] = round($_FILES['file']['size'] / (1024 * 1024), 1);    
}

echo json_encode($response);
