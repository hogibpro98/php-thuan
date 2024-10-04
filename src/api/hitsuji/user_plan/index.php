<?php

//=====================================================================
// 予定情報送信API
//=====================================================================

/* ===================================================
 * 初期処理
 * ===================================================
 */
/*--共通関数-------------------------------------------------*/
//require_once(dirname(__FILE__).'/../../../../common/php/com_ini.php');
//require_once(dirname(__FILE__).'/../../../../common/php/com_calendar.php');
//require_once(dirname(__FILE__).'/../../../../common/php/func_encode.php');
//require_once(dirname(__FILE__).'/../../../../common/php/func_db.php');
//require_once(dirname(__FILE__).'/../../../../common/php/func_get.php');
//require_once(dirname(__FILE__).'/../../../../common/php/func_curl.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_ini.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_calendar.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_encode.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_get.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/func_curl.php');

/*--変数定義-------------------------------------------------*/

// 初期化
$err      = array();
$dispData = array();
$tgtData  = array();
$userId   = null;
$ofcId    = null;
$planIds  = array();
$svcData  = array();

// 初期値
$dispData['result'] = 0;
$dispData['error']  = '';
$dispData['kbn']    = 0;
$dispData['plan']   = array();

//システムユーザー
$loginUser['id'] = 'system';

/* ===================================================
 * 入力情報取得
 * ===================================================
 */

// 従業員マスタ
$stfMst = getData('mst_staff');

// サービス詳細マスタ
$svcMst = getData('mst_service_detail');

/*-- 検索用パラメータ ---------------------------------------*/

// 法人番号
$cmpNo = h(filter_input(INPUT_GET, 'hojin_no'));
if ($cmpNo != HTJ_CMP_NO) {
    $dispData['result'] = 1;
    $dispData['error']  = '法人番号が異なります';
}

// 事業所番号
$ofcNo = h(filter_input(INPUT_GET, 'jigyo_no'));
if (!$ofcNo) {
    $dispData['result'] = 1;
    $dispData['error']  = '事業所番号の指定が不正です';
}

// 利用者番号
$usrNo = h(filter_input(INPUT_GET, 'riyo_no'));
if (!$usrNo) {
    $dispData['result'] = 1;
    $dispData['error']  = '利用者番号の指定が不正です';
}

// 対象年月
$month = h(filter_input(INPUT_GET, 'month'));
$month = str_replace('/', '-', $month);
if (!$month) {
    $dispData['result'] = 1;
    $dispData['error']  = '対象月の指定が不正です';
}

/*-- パラメータ変換 -----------------------------------------*/

// 利用者ID
$where = array();
$where['other_id'] = $usrNo;
$temp = getData('mst_user', $where);
foreach ($temp as $val) {
    $userId = $val['unique_id'];
}

// 事業所ID
$where = array();
$where['other_code'] = $ofcNo;
$temp = getData('mst_office', $where);
foreach ($temp as $val) {
    $ofcId = $val['unique_id'];
}

/* ===================================================
 * データ取得
 * ===================================================
 */


/* -- データ取得 --------------------------------------------*/

// 利用者予定(親)
$where = array();
$where['use_day LIKE'] = $month;
$where['user_id']      = $userId;
if ($ofcNo != 'all') {
    $where['office_id'] = $ofcId;
}
$planData = getData('dat_user_plan', $where);
foreach ($planData as $val) {
    $planIds[] = $val['unique_id'];
}
// サービス詳細(子)
$where = array();
$where['user_plan_id'] = $planIds;
$temp = getData('dat_user_plan_service', $where);
foreach ($temp as $tgtId => $val) {
    $planId = $val['user_plan_id'];
    $svcData[$planId][$tgtId] = $val;
}

/* -- データ整形 --------------------------------------------*/
foreach ($planData as $planId => $planVal) {

    // 区分判定
    if (mb_strpos($planVal['service_name'], '看多機') !== false) {
        $dispData['kbn'] = 1;
    }

    // 初期化
    $pln = array();
    $pln['detail'] = array();

    // 予定日、ステータス、開始時刻、終了時刻
    $pln['plan_day']   = formatDateTime($planVal['use_day'], 'Y/m/d');
    $pln['status']     = $planVal['status'] == '実施' ? 1 : 0;
    $pln['start_time'] = !empty($planVal['start_time'])
                       ? mb_substr($planVal['start_time'], 0, 5)
                       : null;
    $pln['end_time'] = !empty($planVal['end_time'])
                       ? mb_substr($planVal['end_time'], 0, 5)
                       : null;
    // サービス名、文書ID
    $pln['svc_name'] = $planVal['service_name'];
    //$pln['doc_id']   = mb_strpos($planVal['kantaki'],'vis2') !== false
    //        ? $planVal['kantaki']
    //        : null;
    $pln['doc_id']   = $planVal['kantaki'];

    // サービス提供事業所、事業所番号
    $pln['jigyo_name'] = null;
    $pln['jigyo_no'] = null;
    $tgtOfc = $planVal['office_id'];
    $where = array();
    $where['unique_id'] = $tgtOfc;
    //$where['type'] = $pln['kbn'] == 1 ? '看多機' : '訪問看護';
    $ofcAry = getData('mst_office', $where);
    if ($ofcAry) {
        $pln['jigyo_name'] = $ofcAry['name'];
        $pln['jigyo_no']   = $ofcAry['other_code'];
    }

    // サービス詳細
    foreach ($svcData[$planId] as $svcId => $svcVal) {

        // 初期化
        $dtl = array();

        // サービス種類名
        $dtlId = $svcVal['service_detail_id']
                ? $svcVal['service_detail_id']
                : 'dummy' ;
        $dtl['detail_name'] = isset($svcMst[$dtlId]['name'])
                ? $svcMst[$dtlId]['name']
                : null;

        // 訪問介護員名、訪問介護員ID
        $stfId = $svcVal['staff_id']
                ? $svcVal['staff_id']
                : 'dummy';
        $dtl['person_id']   = isset($stfMst[$stfId])
                ? $stfMst[$stfId]['last_name'] . $stfMst[$stfId]['first_name']
                : null;
        $dtl['person_id']   = isset($stfMst[$stfId])
                ? $stfMst[$stfId]['staff_id']
                : null;

        // 格納
        $pln['detail'][] = $dtl;
    }
    // 格納
    $dispData['plan'][] = $pln;
}

// 予定がない場合はエラーとする対応追加
//if (!$dispData['plan']){
//    $dispData['result'] = 1;
//    $dispData['error']  = '該当のスケジュールがありません';
//}


/* -- データ送信 --------------------------------------------*/
if ($dispData['result'] == 1) {
    header("HTTP/1.1 400 Bad Request");
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($dispData, JSON_UNESCAPED_UNICODE);
} else {
    header("HTTP/1.1 200 OK");
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($dispData, JSON_UNESCAPED_UNICODE);
}
exit;
