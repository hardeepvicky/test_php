<?php

$a = ["hardeep" => 34, "vicky" => 21];
$b = ["hardeep"=> 33, "vicky" => 22];

echo "<pre>";
var_dump($a + $b);
var_dump(array_merge($a, $b));


$a = ["hardeep", "vicky"];
$b = [3 => "hardeep", "vicky" ];

echo "<pre>";
var_dump($a + $b);
var_dump(array_merge($a, $b));