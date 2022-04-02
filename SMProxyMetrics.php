<?php

use \Prometheus\CollectorRegistry;
use \Prometheus\Storage\InMemory;
use \Prometheus\RenderTextFormat;

/**
 * @author dreammo
 * @link https://github.com/dream-mo/smproxy-export
 *
 * Class SMProxyMetrics
 *
 */
class SMProxyMetrics
{
    /**
     * @var string
     *
     */
    private $workDir = '';

    /**
     * SMProxyMetrics constructor.
     * @param $workDir
     *
     */
    public function __construct($workDir = '/usr/local/smproxy/')
    {
        $this->workDir = $workDir;
    }

    /**
     * @return mixed
     *
     * get render metrics content string
     *
     */
    public function getRenderMetricsContent()
    {
        // init export CollectorRegistry
        $registry = new CollectorRegistry(new InMemory());

        // exec SMProxy command get metrics
        $work_dir = $this->workDir;
        $shell_result = shell_exec($work_dir . "SMProxy status");
        $db_config = file_get_contents($work_dir . "conf/database.json");
        $db_config = json_decode($db_config, true);
        $lists = explode("\n", $shell_result);
        $swoole_meta = $lists[2];

        // swoole version and number of worker processes
        $swoole_version = str_replace('SwooleVersion: ', '', explode(",", $swoole_meta)[0]);
        $workerNum = str_replace(' WorkerNum:', '', explode(",", $swoole_meta)[1]);
        $swoole_worker_num = trim($workerNum);

        // db_name => dbinfo map
        $db_max_conn = [];
        foreach ($db_config['database']['databases'] as $db_name => $db) {
            $db_max_conn[$db_name] = [
                'maxConns' => intval($db['maxConns'])
            ];
        }

        // set mysql connection metrics
        $connections = $this->analyze_connections($lists);
        foreach ($connections as $db_name => $db_connections) {
            // set db max connection metrics
            $db_max_conn_gauge = $registry->getOrRegisterGauge('sp', 'db_max_connection', 'db max connection number', ['db']);
            $db_max_conn_gauge->set($db_max_conn[$db_name]['maxConns'], [$db_name]);
            foreach ($db_connections as $db_connection) {
                // set connection detail info
                $connection_info_time_gauge = $registry->getOrRegisterGauge('sp', 'connection_info_time', 'current connection info => time',
                    ['id', 'client', 'user', 'db', 'command', 'state', 'info', 'server_status', 'server_key']);
                $connection_info_time_gauge->set($db_connection['TIME'], [$db_connection['ID'], $db_connection['HOST'],
                    $db_connection['USER'], $db_connection['DB'], $db_connection['COMMAND'], $db_connection['STATE'], $db_connection['INFO'],
                    $db_connection['SERVER_STATUS'], $db_connection['SERVER_KEY']]);
                $connection_info_server_status_gauge = $registry->getOrRegisterGauge('sp', 'connection_info_server_status', 'current connection => server_status',
                    ['id', 'client', 'user', 'db', 'command', 'state', 'info', 'server_key']);
                $connection_info_server_status_gauge->set($db_connection['SERVER_STATUS'], [$db_connection['ID'], $db_connection['HOST'],
                    $db_connection['USER'], $db_connection['DB'], $db_connection['COMMAND'], $db_connection['STATE'],
                    $db_connection['INFO'], $db_connection['SERVER_KEY']]);
            }
        }

        // swoole metrics
        $swoole_worker_num_gauge = $registry->getOrRegisterGauge('sp', 'swoole_worker_num', 'swoole worker number');
        $swoole_worker_num_gauge->set($swoole_worker_num);
        $swoole_version_gauge = $registry->getOrRegisterGauge('sp', 'swoole_version', 'swoole version', ['version']);
        $swoole_version_gauge->set(1, [$swoole_version]);

        // get render metrics string
        $renderer = new RenderTextFormat();
        return $renderer->render($registry->getMetricFamilySamples());
    }

    /**
     * @param $lists
     * @return array
     *
     * Extract msyql connection number information
     *
     */
    private function analyze_connections($lists)
    {
        $connection_tables = array_slice($lists, 7, count($lists) - 10);
        $connections = [];
        foreach ($connection_tables as $index => $line) {
            $line = str_replace('  ', '', $line);
            $fields = explode('|', $line);
            unset($fields[count($fields) - 1], $fields[0]);
            $fields = array_values($fields);
            $fields = array_map(function ($val) {
                return trim($val);
            }, $fields);
            $connection = [
                'ID' => $fields[0],
                'USER' => $fields[1],
                'HOST' => $fields[2],
                'DB' => $fields[3],
                'COMMAND' => $fields[4],
                'TIME' => $fields[5],
                'STATE' => $fields[6],
                'INFO' => $fields[7],
                'SERVER_VERSION' => $fields[8],
                'PLUGIN_NAME' => $fields[9],
                'SERVER_STATUS' => $fields[10],
                'SERVER_KEY' => $fields[11]
            ];
            $connections[$fields[3]][] = $connection;
        }
        return $connections;
    }

    /**
     * @return string
     *
     * get default page
     *
     */
    public function defaultPage()
    {
        $html = '<html><head><title>SMPorxyExporter</title></head><body><h1>SMPorxy Exporter</h1><p><a href="/metrics">Metrics</a></p></body></html>';
        return $html;
    }
}
