# smproxy-export
Prometheus export of SMproxy database connection pool.

SMProxy 数据库连接池 promethues exporter.

SMProxy project github: [smproxy](git@github.com:dream-mo/SMProxy-export.git)

# how to use

```shell
git clone git@github.com:dream-mo/smproxy-export.git

docker-compose up -d --build

curl localhost:9137/metrics
```
# Grafana Dasbord

view grafana-smproxy-dashbord.json or import dashbord id: 【16036】 [dashbord](https://grafana.com/grafana/dashboards/16036)

![dashbord](https://github.com/dream-mo/SMProxy-export/blob/main/SMProxy-dashbord-show.png)

# implementation principle

PHP(index.php) exec shell_exec SMPorxy command get metrics.

实现原理: index.php执行shell_exec命令, 通过清洗SMProxy status的输出指标数据
```shell
/usr/local/SMProxy status
```

# metrics
```html
# HELP php_info Information about the PHP environment.
# TYPE php_info gauge
php_info{version="7.2.13"} 1

# HELP sp_connection_info_server_status current connection => server_status
# TYPE sp_connection_info_server_status gauge
sp_connection_info_server_status{id="868",client="192.168.144.1:56588",user="root",db="hello",command="Sleep",state="",info="",server_key="writeSΜhello"} 2

# HELP sp_connection_info_time current connection info => time

# TYPE sp_connection_info_time gauge
sp_connection_info_time{id="868",client="192.168.144.1:56588",user="root",db="hello",command="Sleep",state="",info="",server_status="2",server_key="writeSΜhello"} 1604

# HELP sp_db_max_connection db max connection number
# TYPE sp_db_max_connection gauge
sp_db_max_connection{db="hello"} 30

# HELP sp_swoole_version swoole version
# TYPE sp_swoole_version gauge
sp_swoole_version{version="4.8.8"} 1

# HELP sp_swoole_worker_num swoole worker number
# TYPE sp_swoole_worker_num gauge
sp_swoole_worker_num 8
```
