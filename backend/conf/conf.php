<?php

/*=========== RDSホスト ===========*/
$hosts = [
  "n1" => "n1-rds-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "n2" => "n2-rds-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "n3" => "n3-rds-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "n5" => "n5-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "ogsports" => "ogsports-db-api-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "k-kyu-m" => "k-kyu-m-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com",
  "toyamaBX" => "n8-cluster.cluster-c1yab9nk7gtn.ap-northeast-1.rds.amazonaws.com"
];

$ws_api_url_list = [
  "n1" => "http://172.31.13.165:8080/ws/api",
  "n2" => "http://172.31.42.194:8080/ws/api",
  "n3" => "http://172.31.35.21:8080/ws/api",
  "n5" => "http://172.31.35.154:8080/ws/api",
  "ogsports" => "http://172.31.10.251:8080/ws/api",
  "k-kyu-m" => "http://172.31.11.128:8080/ws/api",
  "toyamaBX" => "http://172.31.38.178:8080/ws/api"
];

/*=========== DBログイン情報 ===========*/
$setting = [
  "user" => "admin",
  "password" => "YAvM1Y0DgsEVjwxA"
];

/*=========== ログ出力場所 ===========*/
$log_path = __DIR__."/../backend.log";

/*=========== 取得SQL ===========*/
$select_list = [
  "SELECT device_id FROM m_device WHERE contractor_id = :id",
  "SELECT person_id FROM t_person WHERE contractor_id = :id",
  "SELECT auth_set_id FROM t_auth_set WHERE contractor_id = :id"
];

/*=========== 削除SQL ===========*/
$delete_list_contractor = [
  "DELETE FROM t_alert WHERE contractor_id = :id",
  "DELETE FROM t_apb_group WHERE contractor_id = :id",
  "DELETE FROM t_auth_set WHERE contractor_id = :id",
  "DELETE FROM t_device_group WHERE contractor_id = :id",
  "DELETE FROM t_enter_exit_count WHERE contractor_id = :id",
  "DELETE FROM t_facet_operate_log WHERE contractor_id = :id",
  "DELETE FROM t_person WHERE contractor_id = :id",
  "DELETE FROM t_push_response_msg WHERE contractor_id = :id",
  "DELETE FROM t_recog_config_set WHERE contractor_id = :id",
  "DELETE FROM t_system_config_set WHERE contractor_id = :id",
  "DELETE FROM m_person_type WHERE contractor_id = :id",
  "DELETE FROM m_recog_pass_flag WHERE contractor_id = :id",
  "DELETE FROM m_user WHERE contractor_id = :id",
  "DELETE FROM m_device WHERE contractor_id = :id",
  "DELETE FROM m_contractor WHERE contractor_id = :id"
];
$init_list_contractor = [
  "DELETE FROM t_alert WHERE contractor_id = :id",
  "DELETE FROM t_apb_group WHERE contractor_id = :id",
  "DELETE FROM t_auth_set WHERE contractor_id = :id",
  "DELETE FROM t_device_group WHERE contractor_id = :id",
  "DELETE FROM t_enter_exit_count WHERE contractor_id = :id",
  "DELETE FROM t_facet_operate_log WHERE contractor_id = :id",
  "DELETE FROM t_person WHERE contractor_id = :id",
  "DELETE FROM t_push_response_msg WHERE contractor_id = :id",
  "DELETE FROM t_recog_config_set WHERE contractor_id = :id",
  "DELETE FROM t_system_config_set WHERE contractor_id = :id",
  "DELETE FROM m_person_type WHERE contractor_id = :id",
  "DELETE FROM m_recog_pass_flag WHERE contractor_id = :id",
  "DELETE FROM m_user WHERE contractor_id = :id AND user_flag <> 1",
  "DELETE FROM m_device WHERE contractor_id = :id AND serial_no <> '99999999999'"
];
$delete_list_device = [
  "DELETE FROM t_alert_device WHERE device_id = :id",
  "DELETE FROM t_apb_group_device WHERE device_id = :id",
  "DELETE FROM t_apb_log WHERE device_id = :id",
  "DELETE FROM t_apb_log_device WHERE trans_device_id = :id",
  "DELETE FROM t_apb_repair WHERE device_id = :id",
  "DELETE FROM t_device_group_device WHERE device_id = :id",
  "DELETE FROM t_device_person WHERE device_id = :id",
  "DELETE FROM t_operate_log WHERE device_id = :id",
  "DELETE FROM t_person_access_time WHERE device_id = :id",
  "DELETE FROM t_recog_analize WHERE device_id = :id",
  "DELETE FROM t_recog_analize_daily WHERE device_id = :id",
  "DELETE FROM t_recog_analize_hourly WHERE device_id = :id",
  "DELETE FROM t_recog_log WHERE device_id = :id",
  "DELETE FROM t_sync_log WHERE device_id = :id",
];

$delete_list_person = [
  "DELETE FROM t_person_card_info WHERE person_id = :id"
];

$delete_list_auth = [
  "DELETE FROM t_function_auth WHERE auth_set_id = :id"
];

// SELECTする配列「$select_list」の順番で格納する必要がある
$delete_list = [
  $delete_list_device,
  $delete_list_person,
  $delete_list_auth,
  $delete_list_contractor
];
$init_list = [
  $delete_list_device,
  $delete_list_person,
  $delete_list_auth,
  $init_list_contractor
];

/*=========== 環境バージョン ===========*/
$version = [
  "n1" => 4.1,
  "n2" => 3.5,
  "n3" => 4,
  "n5" => 4,
  "ogsports" => 4,
  "k-kyu-m" => 4.1,
  "toyamaBX" => 4.1,
];
