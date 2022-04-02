<?php

require_once("./vendor/autoload.php");
require_once('./SMProxyMetrics.php');
error_reporting(0);

$sp_metrics = new SMProxyMetrics();
$uri = $_SERVER['REQUEST_URI'];
if ($uri != '/metrics') {
    header('Content-Type: text/html;charset=utf-8');
    echo $sp_metrics->defaultPage();
}else{
    header('Content-Type: text/plain; charset=utf-8');
    echo $sp_metrics->getRenderMetricsContent();
}
