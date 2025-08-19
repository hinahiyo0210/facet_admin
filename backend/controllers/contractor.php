<?php

include(__DIR__ . "/../conf/conf.php");

class ContractorController {
    public $code = 200;
    public $url;
    public $request_body;

    function __construct() {
        $this->url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].mb_substr($_SERVER['SCRIPT_NAME'],0,-9).basename(__FILE__, ".php")."/";
        $this->request_body = json_decode(mb_convert_encoding(file_get_contents('php://input'),"UTF8","ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN"),true);
    }

    public function get($tenant, $id = null):array {
        $db = new DB($tenant);
        echo $_GET["data"];
        if($this->is_set($id)){
            return $this->getById($db, $id);
        }else{
            return $this->getAll($db);
        }
    }

    private function getById($db, $id):array {
        $sql = "SELECT * FROM m_contractor WHERE contractor_id = :id";
        $sth = $db->pdo()->prepare($sql);
        $sth->bindValue(":id",$id);
        $res = $sth->execute();
        if($res){
            $data = $sth->fetch(PDO::FETCH_ASSOC);
            if(!empty($data)){
                return $data;
            }else{
                $this->code = 404;
                return ["error" => [
                    "type" => "not_in_contractor"
                ]];
            }
        }else{
            $this->code = 500;
            return ["error" => [
                "type" => "fatal_error"
            ]];
        }
    }

    private function getAll($db):array {
        $sql = "SELECT * FROM m_contractor";
        $sth = $db->pdo()->prepare($sql);
        $res = $sth->execute();
        if($res){
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }else{
            $this->code = 500;
            return ["error" => [
                "type" => "fatal_error"
            ]];
        }
    }

    public function post($tenant, $id = null):array {
        $this->code = 200;
        if($this->is_set($id)){
            return $this->update($tenant, $id);
        }else{
            return $this->add($tenant);
        }
    }

    public function add($tenant):array {
        $log = new LOG();
     
        global $ws_api_url_list;
        global $version;
        
        $post = $this->request_body;
        $result_arr = [];
        
        $log->info($_SERVER["PHP_AUTH_USER"], "登録処理の開始:{\"params\":".json_encode($post)."}", date('Y-m-d H:i:s'));

        // fix_typeがadd以外ではないことを確認
        if ($post["fix_type"] !== "add") {
            $log->error($_SERVER["PHP_AUTH_USER"],"fix_typeの不一致:{\"fix_type\":\"".$post["fix_type"]."\"}", date('Y-m-d H:i:s'));

            $this->code = 400;
            return ["msg" => "不正の区分が判定されました。"];
        }

        // if(!array_key_exists("id",$post) || !array_key_exists("name",$post) || !array_key_exists("age",$post)){
        //     $this->code = 400;
        //     return ["error" => [
        //         "type" => "invalid_param"
        //     ]];
        // }

        // AutoIncrementの次の数値を取得
        $db = new DB($tenant);
        // $sql = "SHOW TABLE STATUS WHERE Name = 'm_contractor'";
        // $sth = $db->pdo()->prepare($sql);
        // $res = $sth->execute();
        // $contractor_status = $sth->fetch(PDO::FETCH_ASSOC);
        // $next_auto_num = $contractor_status["Auto_increment"];

        // api_tokenの格納
        $chars = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $api_token = substr(str_shuffle(str_repeat($chars, 64)), 0, 64);
        
        // api_tokenの重複確認と重複時の再格納
        $sql = "SELECT api_token FROM m_contractor WHERE state <> 90";
        $sth = $db->pdo()->prepare($sql);
        $res = $sth->execute();
        $api_token_all = $sth->fetchAll(PDO::FETCH_ASSOC);
        for ($i=0; $i < 10; $i++) { 
          if (in_array($api_token, array_column($api_token_all, 'api_token'))) {
            $api_token = substr(str_shuffle(str_repeat($chars, 64)), 0, 64);
            continue;
          } else {
            break;
          }
        }

        // s3_path_prefixの格納
        // $s3_path_prefix = substr('00000'.$next_auto_num, -6).'/person';
        
        // stateの格納
        $state = ($post["trial_limit"]) ? 20 : 30;

        // ===== m_contractorへのInsert処理 =====
        // =====================================
        $sql = "INSERT INTO 
            m_contractor (
              contractor_name,
              state,
              create_time,
              trial_limit,
              api_allow_ip,
              api_token,
              s3_path_prefix,
              save_person_name_flag,
              apb_mode_flag,
              domain,
              allow_device_num,
              enter_exit_mode_flag";
        if ($version[$tenant] >= 4.1) {
          $sql .= ",enter_exit_description_name1,enter_exit_description_name2";
        }
        $sql .= ") VALUES (
              :contractor_name,
              :state,
              now(),
              :trial_limit,
              :api_allow_ip,
              :api_token,
              :s3_path_prefix,
              1,
              :apb_mode_flag,
              :domain,
              :allow_device_num,
              :ee_mode_flag";
        if ($version[$tenant] >= 4.1) {
          $sql .= ",:ee_desc_1,:ee_desc_2";
        }
        $sql .= ")";
        $pdo = $db->pdo();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(":contractor_name",$post["contractor_name"]);
        $sth->bindValue(":state", $state);
        $sth->bindValue(":trial_limit", $post["trial_limit"]);
        $sth->bindValue(":api_allow_ip", $post["api_allow_ip"]);
        $sth->bindValue(":api_token", $api_token);
        $sth->bindValue(":s3_path_prefix", '');
        // $sth->bindValue(":s3_path_prefix", $s3_path_prefix);
        $sth->bindValue(":apb_mode_flag", $post["apb_mode_flag"]);
        $sth->bindValue(":domain", $post["domain"]);
        $sth->bindValue(":allow_device_num", $post["allow_device_num"]);
        $sth->bindValue(":ee_mode_flag", $post["ee_mode_flag"]);
        if ($version[$tenant] >= 4.1) {
          $sth->bindValue(":ee_desc_1", $post["ee_desc_1"]);
          $sth->bindValue(":ee_desc_2", $post["ee_desc_2"]);
        }
        $res = $sth->execute();
        $contractor_id = $pdo->lastInsertId();
        if ($res) {
          array_push($result_arr, true);
          $log->info($_SERVER["PHP_AUTH_USER"],"contractor登録処理完了:{\"contractor_id\":".$contractor_id.",\"api_token\":\"".$api_token."\"}", date('Y-m-d H:i:s'));
        } else {
          array_push($result_arr, false);
          $log->error($_SERVER["PHP_AUTH_USER"],"contractor登録処理失敗", date('Y-m-d H:i:s'));
        }

        // ======== m_contractorへのUpdate処理 ========
        // ===========================================
        $s3_path_prefix = substr('00000'.$contractor_id, -6).'/person';
        $sql = "UPDATE m_contractor SET s3_path_prefix=:s3_path_prefix WHERE contractor_id=:id";
        $sth = $db->pdo()->prepare($sql);
        $sth->bindValue(":id", $contractor_id);
        $sth->bindValue(":s3_path_prefix", $s3_path_prefix);
        $res = $sth->execute();

        if($res){
            array_push($result_arr, true);
            $log->info($_SERVER["PHP_AUTH_USER"],"m_contractor[s3_path_prefix]更新処理完了:{\"contractor_id\":".$contractor_id.",\"s3_path_prefix\":\"".$s3_path_prefix."\"}", date('Y-m-d H:i:s'));
        }else{
           array_push($result_arr, false);
            $log->error($_SERVER["PHP_AUTH_USER"],"m_contractor[s3_path_prefix]更新処理失敗", date('Y-m-d H:i:s'));
        }


        // ======= m_deviceへのInsert処理 =======
        // =====================================
        $sql = "
          INSERT INTO 
          m_device (
            device_type,
            serial_no,
            contractor_id,
            sort_order,
            create_time,
            contract_state,
            start_date,
            save_recog_picture_flag,
            save_recog_name_flag,
            ws_api_url,
            s3_path_prefix,
            picture_check_device_flag
          ) VALUES (
            'XXXXXXXXXX',
            99999999999,
            :contractor_id,
            1,
            now(),
            99,
            CURDATE(),
            1,
            1,
            :ws_api_url,
            :s3_path_prefix,
            0
          )";

        $pdo = $db->pdo();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(":contractor_id", $contractor_id);
        $sth->bindValue(":ws_api_url", $ws_api_url_list[$tenant]);
        $sth->bindValue(":s3_path_prefix", substr('00000'.$contractor_id, -6).'/picture/XXXXXXXXXX');
        $res = $sth->execute();
        $id = $pdo->lastInsertId();
        if ($res) {
          array_push($result_arr, true);
          $log->info($_SERVER["PHP_AUTH_USER"],"device登録処理完了:{\"contractor_id\":".$contractor_id.",\"device_id\":".$id."}", date('Y-m-d H:i:s'));
        } else {
          array_push($result_arr, false);
          $log->error($_SERVER["PHP_AUTH_USER"],"device登録処理失敗", date('Y-m-d H:i:s'));
        }
        

        // ======== m_userへのInsert処理 ========
        // =====================================
        $sql = "
          INSERT INTO 
          m_user (
            create_time,
            update_time,
            contractor_id,
            login_id,
            password,
            user_name,
            state,
            access_time,
            allow_ips
          ) VALUES (
            now(),
            now(),
            :contractor_id,
            :login_id,
            'd7d8b303e3997c4780f53f410b3855c901bada57',
            :user_name,
            10,
            now(),
            :allow_ips
          )";

        $pdo = $db->pdo();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(":contractor_id", $contractor_id);
        $sth->bindValue(":login_id", $post["login_id"]);
        $sth->bindValue(":user_name", $post["user_name"]);
        $sth->bindValue(":allow_ips", $post["allow_ips"]);
        $res = $sth->execute();
        $id = $pdo->lastInsertId();
        if ($res) {
            array_push($result_arr, true);
            $log->info($_SERVER["PHP_AUTH_USER"],"user登録処理完了:{\"contractor_id\":".$contractor_id.",\"user_id\":".$id.",\"login_id\":\"".$post["login_id"]."\"}", date('Y-m-d H:i:s'));
        } else {
            array_push($result_arr, false);
            $log->error($_SERVER["PHP_AUTH_USER"],"user登録処理失敗", date('Y-m-d H:i:s'));
        }
        
        if(in_array(false, $result_arr)){
            // どこかの処理で失敗している場合、falseが入っている
            $log->error($_SERVER["PHP_AUTH_USER"],"登録処理失敗", date('Y-m-d H:i:s'));

            $this->code = 400;
            return ["msg" => "登録処理が失敗しました。改めてお試しください。"];
        }else{
            $log->info($_SERVER["PHP_AUTH_USER"],"登録処理完了:{\"contractor_id\":".$contractor_id."}", date('Y-m-d H:i:s'));

            $this->code = 201;
            header("Location: ".$this->url.$id);
            return [
              "msg" => "登録処理が完了しました。",
              "contractor_id" => $contractor_id,
              "api_token" => $api_token
            ];
        }

    }

    public function update($tenant, $id = null):array {
        $log = new LOG();
      
        global $version;

        $result_arr = [];

        // 更新なのにidが送信されていない場合
        if(!$this->is_set($id)){
           $log->error($_SERVER["PHP_AUTH_USER"],"contractor_id不足:{\"contractor_id\":".$tenant."}", date('Y-m-d H:i:s'));
            $this->code = 400;
            return ["msg" => "更新のためのcontractor_idが不足しています。"];
        }
        $post = $this->request_body;

        // fix_typeがadd以外ではないことを確認
        if ($post["fix_type"] !== "edit") {
            $this->code = 400;
            return ["msg" => "不正の区分が判定されました。"];
        }

        // DB接続
        $db = new DB($tenant);

        // stateの格納
        $state = ($post["trial_limit"]) ? 20 : 30;

        // ======== m_contractorへのUpdate処理 ========
        // ===========================================
        $sql = "
          UPDATE m_contractor
            SET
              state=:state,
              trial_limit=:trial_limit,
              api_allow_ip=:api_allow_ip,
              apb_mode_flag=:apb_mode_flag,
              domain=:domain,
              allow_device_num=:allow_device_num,
              enter_exit_mode_flag=:ee_mode_flag";
        if ($version[$tenant] >= 4.1) {
          $sql .= "
            ,enter_exit_description_name1=:ee_desc_1
            ,enter_exit_description_name2=:ee_desc_2";
        }
        $sql .= " WHERE contractor_id=:id";

        $sth = $db->pdo()->prepare($sql);
        $sth->bindValue(":id", $id);
        $sth->bindValue(":state", $state);
        $sth->bindValue(":trial_limit", $post["trial_limit"]);
        $sth->bindValue(":api_allow_ip", $post["api_allow_ip"]);
        $sth->bindValue(":apb_mode_flag", $post["apb_mode_flag"]);
        $sth->bindValue(":domain", $post["domain"]);
        $sth->bindValue(":allow_device_num", $post["allow_device_num"]);
        $sth->bindValue(":ee_mode_flag", $post["ee_mode_flag"]);
        if ($version[$tenant] >= 4.1) {
          $sth->bindValue(":ee_desc_1", $post["ee_desc_1"]);
          $sth->bindValue(":ee_desc_2", $post["ee_desc_2"]);
        }
        $res = $sth->execute();

        if($res){
            array_push($result_arr, true);
            $log->info($_SERVER["PHP_AUTH_USER"],"m_contractor更新処理完了:{\"contractor_id\":".$id."}", date('Y-m-d H:i:s'));
        }else{
           array_push($result_arr, false);
            $log->error($_SERVER["PHP_AUTH_USER"],"m_contractor更新処理失敗", date('Y-m-d H:i:s'));
        }

        // ======== m_userのIP制限のUpdate処理 ========
        // ===========================================
        $sql = "UPDATE m_user SET allow_ips=:allow_ips WHERE contractor_id=:id";
        
        $sth = $db->pdo()->prepare($sql);
        $sth->bindValue(":id", $id);
        $sth->bindValue(":allow_ips", $post["allow_ips"]);
        $res = $sth->execute();

        if($res){
            array_push($result_arr, true);
            $log->info($_SERVER["PHP_AUTH_USER"],"GUIのIP制限更新処理完了:{\"contractor_id\":".$id.",\"allow_ips\":\"".$post["allow_ips"]."\"}", date('Y-m-d H:i:s'));
        }else{
            array_push($result_arr, false);
            $log->error($_SERVER["PHP_AUTH_USER"],"GUIのIP制限更新処理失敗", date('Y-m-d H:i:s'));
        }

        if(in_array(false, $result_arr)){
          $log->error($_SERVER["PHP_AUTH_USER"],"更新処理失敗", date('Y-m-d H:i:s'));
          $this->code = 500;
          return ["msg" => "一部更新処理が失敗しました。改めて確認・お試しください。"];
        }else{
          $log->info($_SERVER["PHP_AUTH_USER"],"更新処理完了:{\"contractor_id\":".$id."}", date('Y-m-d H:i:s'));
          return ["msg" => "更新処理が完了しました。"];
        }

    }

    public function options():array {
        header("Access-Control-Allow-Methods: OPTIONS,GET,HEAD,POST,PUT,DELETE");
        header("Access-Control-Allow-Headers: Content-Type");
        return [];
    }

    private function is_set($value):bool {
        return !(is_null($value) || $value === "");
    }
}