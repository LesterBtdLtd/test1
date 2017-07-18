<?php
error_reporting(E_ALL);

if(!defined('STDIN')) exit;

include "CollectPageRefs.php";
//include "CollectPageRefs2.php";


$CPR = new CollectPageRefs($argv);
//$CPR = new CollectPageRefs2($argv);
try
{
    $CPR->parse();
}
catch (CollectPageRefsException $ex)
{
    echo $ex->getCode() . ': ' . $ex->getMessage() . PHP_EOL;
}
catch (Exception $ex)
{
    echo $ex->getCode() . ': ' . $ex->getMessage() . PHP_EOL;
}