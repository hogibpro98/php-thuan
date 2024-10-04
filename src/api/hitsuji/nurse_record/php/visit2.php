<?php

//=====================================================================
// 訪問看護2
//=====================================================================
try {
    /* ===================================================
     * 初期処理
     * ===================================================
     */


    /*--共通ファイル呼び出し-------------------------------------*/
    //require_once($_SERVER['DOCUMENT_ROOT'].'/common/php/com_start.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_ini.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_calendar.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_encode.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_db.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_get.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_curl.php');
    /*--変数定義-------------------------------------------------*/

    // 初期化
    $err      = array();
    $_SESSION['notice']['error'] = array();
    $dispData = array();
    $dispPbl  = array();
    $tgtData  = array();
    $upData   = array();
    $upPbl    = array();
    $prbData  = array();
    $otherWindowURL = array();

    // 対象テーブル
    $table1 = 'doc_visit2';
    $table2 = 'doc_visit2_problem';

    // 初期値
    $dispData = initTable($table1);
    $dispFml  = array();
    $dispFcl  = array();
    $keyId    = null;
    $dispData['other_id']    = null;
    $dispData['user_name']   = null;
    $week = formatDateTime(NOW, 'w');
    $weekDisp = '(' . $weekAry[$week] . ')';
    $dispData['disp_report'] = formatDateTime(NOW, 'Y年m月d日') . $weekDisp;
    $dispData['disp_first']  = $dispData['disp_report'];
    $dispData['staff1_name'] = null;
    $dispData['staff2_name'] = null;
    $dispData['create_day']  = null;
    $dispData['create_time'] = null;
    $dispData['create_name'] = null;
    $dispData['update_day']  = null;
    $dispData['update_time'] = null;
    $dispData['update_name'] = null;
    $dispData['staff1_cd']   = null;
    $dispData['staff2_cd']   = null;

    $selHour = ['','00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
    $selMinutes = ['','00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'];

    /* ===================================================
     * 入力情報取得
     * ===================================================
     */

    /*-- 検索用パラメータ ---------------------------------------*/

    // KEY
    $docId = filter_input(INPUT_GET, 'doc_id');
    $keyId = filter_input(INPUT_GET, 'id');
    if (!$keyId && $docId) {
        $keyId = $docId;
    }

    // 利用者ID
    $userId = filter_input(INPUT_GET, 'user');
    if (!$userId) {
        $userId = !empty($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    /*-- 更新用パラメータ ---------------------------------------*/



    /*-- その他パラメータ ---------------------------------------*/

    /* ===================================================
     * イベント前処理(更新用配列作成、入力チェックなど)
     * ===================================================
     */

    /* -- 更新用配列作成 ----------------------------------------*/


    /* -- 削除用配列作成 ----------------------------------------*/


    /* ===================================================
     * イベント本処理(データ登録)
     * ===================================================
     */



    /* ===================================================
     * イベント後処理(描画用データ作成)
     * ===================================================
     */



    /* -- データ取得 --------------------------------------------*/

    /* -- 汎用マスタ ---------------------------*/
    $gnrList = getCode('訪問看護記録Ⅱ詳細');

    /* -- 利用者マスタ -------------------------*/
    $where = array();
    $where['delete_flg'] = 0;
    $target = 'unique_id,last_name,first_name,other_id';
    $temp = select('mst_user', $target, $where);
    foreach ($temp as $val) {
        $tgtId      = $val['unique_id'];
        $lastName   = $val['last_name'];
        $firstName  = $val['first_name'];
        $val['name'] = $lastName . ' ' . $firstName;
        $userList[$tgtId] = $val;
    }
    if ($userId && isset($userList[$userId])) {
        $dispData['other_id']  = $userList[$userId]['other_id'];
        $dispData['user_name'] = $userList[$userId]['name'];
    }

    /* -- スタッフマスタ -----------------------*/
    $where = array();
    $where['delete_flg'] = 0;
    $target = 'unique_id,last_name,first_name';
    $temp = select('mst_staff', '*', $where);
    foreach ($temp as $val) {
        $tgtId       = $val['unique_id'];
        $staffList[$tgtId] = $val;
    }

    /* -- 訪問介護記録Ⅱ ------------------------*/
    if ($keyId) {

        // データ取得
        $where = array();
        $where['delete_flg'] = 0;
        $where['unique_id']  = $keyId;
        $temp = select($table1, '*', $where);

        if (isset($temp[0])) {

            // テーブル値
            $tgtData = $temp[0];

            // 利用者ID、スタッフ１、スタッフ２
            if (!empty($tgtData['user_id'])) {
                $userId = $tgtData['user_id'];
            }
            $tgtData['staff1_name'] = getStaffName($tgtData['staff1_id']);
            $tgtData['staff2_name'] = getStaffName($tgtData['staff2_id']);
            $tgtData['staff1_cd'] = isset($staffList[$tgtData['staff1_id']]['staff_id']) ? $staffList[$tgtData['staff1_id']]['staff_id'] : '';
            $tgtData['staff2_cd'] = isset($staffList[$tgtData['staff2_id']]['staff_id']) ? $staffList[$tgtData['staff2_id']]['staff_id'] : '';

            if (empty($tgtData['service_day']) && empty($tgtData['start_time']) && empty($tgtData['end_time'])) {

                $where = array();
                $where['delete_flg'] = 0;
                $where['unique_id']  = $tgtData['target_plan_id'];
                $plans = select('dat_user_plan', '*', $where);
                if (isset($plans[0])) {
                    $tgtData['service_day'] = $plans[0]['use_day'];
                    $tgtData['start_time'] = $plans[0]['start_time'];
                    $tgtData['end_time'] = $plans[0]['end_time'];
                }
            }

            // 初回登録
            $tgtDate = $tgtData['create_date'];
            $tgtData['create_day']  = formatDateTime($tgtDate, 'Y/m/d');
            $tgtData['create_time'] = formatDateTime($tgtDate, 'H:i');
            $tgtData['create_name'] = getStaffName($tgtData['create_user']);

            // 更新情報
            $tgtDate = $tgtData['update_date'];
            $tgtData['update_day']  = formatDateTime($tgtDate, 'Y/m/d');
            $tgtData['update_time'] = formatDateTime($tgtDate, 'H:i');
            $tgtData['update_name'] = getStaffName($tgtData['update_user']);

            $tgtData['start_time'] = formatDateTime($tgtData['start_time'], 'H:i');
            $tgtData['end_time']   = formatDateTime($tgtData['end_time'], 'H:i');
            $tgtData['next_start'] = formatDateTime($tgtData['next_start'], 'H:i');
            $tgtData['next_end']   = formatDateTime($tgtData['next_end'], 'H:i');

            if (empty($tgtData['staff1_id'])) {
                $tgtData['staff1_id'] = $loginUser['unique_id'];
                $tgtData['staff1_name'] = $loginUser['name'];
                $tgtData['staff1_cd'] = $loginUser['staff_id'];
            }

            // 格納
            $dispData = array_merge($dispData, $tgtData);
        }
    }

    /* -- 訪問看護（課題） -----------------------------*/
    if ($keyId) {
        $where = array();
        $where['delete_flg'] = 0;
        $where['visit2_id']  = $keyId;
        $target = '*';
        $temp = select($table2, $target, $where);
        foreach ($temp as $val) {
            //        $tgtId = $val['problem_id'];
            $tgtId = $val['visit2_id'];
            $prbData[$tgtId] = $val;
        }
    }

    /* -- 計画書（課題） -------------------------------*/
    //if ($userId){
    //
    //    // 計画書(親)から課題の利用者ID群を取得
    //    $where = array();
    //    $where['delete_flg'] = 0;
    //    $where['user_id']  = $userId;
    //    // 有効判定追加予定★
    //    $target = 'unique_id';
    //    $temp = select('doc_plan',$target,$where);
    //    $tgtIds = array();
    //    foreach ($temp as $val){
    //        $tgtIds[] = $val['unique_id'];
    //    }
    //
    //    // 課題データ取得
    //    if ($tgtIds){
    //
    //        // DBから取得
    //        $where = array();
    //        $where['delete_flg'] = 0;
    //        $where['plan_id'] = $tgtIds;
    //        $target = 'unique_id, problem';
    //        $temp = select('doc_plan_problem',$target,$where);
    //
    //        // 未反映のデータのみを対象とする
    //        foreach ($temp as $val){
    //            $prbId = $val['unique_id'];
    //            if (!isset($prbData[$prbId])){
    //                $dat = array();
    //                $dat['problem_id'] = $prbId;
    //                $dat['problem']    = $val['problem'];
    //                $dat['comment']    = '';
    //                $prbData[$prbId]   = $dat;
    //            }
    //        }
    //    }
    //}

    // 表示用データ
    $dispPrb = $prbData;

    /* -- その他 --------------------------------------------*/

    // 帳票印刷
    //if ($btnPrint && $keyId){
    //    $otherWindowURL[] = $_SERVER['HTTP_ORIGIN'].$server['scriptName'].'?id='.$keyId.'&prt=true';
    //}
    // 印刷処理
    //if ($prt){
    //
    //    // 出力条件
    //    $search = array();
    //    $search['unique_id'] = $keyId;
    //
    //    $res = printPDF('020', $search);
    //}

    /* ===================================================
     * 例外処理
     * ===================================================
     */
} catch (Exception $e) {
    if ($execEnv === 'pro' || $execEnv === 'stg') {
        $_SESSION['err'] = !empty($err) ? $err : array();
        header("Location:" . ERROR_PAGE);
        exit;
    } else {
        debug($e);
        exit;
    }
}
