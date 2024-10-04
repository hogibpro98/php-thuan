<?php

//=====================================================================
// 帳票一括確認
//=====================================================================
try {
    /* ===================================================
     * 初期処理
     * ===================================================
     */


    /*--共通ファイル呼び出し-------------------------------------*/
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_start.php');

    /*--変数定義-------------------------------------------------*/

    // 初期化
    $err      = array();
    $_SESSION['notice']['error'] = array();
    $dispData = array();
    $userId  = null;
    $tgtUser = array();
    $userMst = array();
    $tgtData = array();

    // 表示件数
    $line = 20;

    $reportNames['褥瘡計画']         = true;
    $reportNames['指示書']        = true;
    $reportNames['計画書']        = true;
    $reportNames['報告書']        = true;
    $reportNames['経過記録']      = true;
    $reportNames['看多機記録']    = true;
    $reportNames['訪問看護記録Ⅰ'] = true;
    $reportNames['訪問看護記録Ⅱ'] = true;

    /* ===================================================
     * 入力情報取得
     * ===================================================
     */

    /*-- 検索用パラメータ ---------------------------------------*/

    // 検索配列
    $search = filter_input(INPUT_GET, 'search', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $search = $search ? $search : array();

    $search['start_day'] = !empty($search['start_day']) ? formatDateTime($search['start_day'], "Y-m-d") : THISMONTHFIRST;
    $search['end_day']   = !empty($search['end_day']) ? formatDateTime($search['end_day'], "Y-m-d") : null;//THISMONTHLAST;
    $search['report']    = !empty($search['report']) ? $search['report'] : null;
    $search['kana']      = !empty($search['kana']) ? $search['kana'] : null;
    $search['status1']   = !empty($search['status1']) ? $search['status1'] : null;
    $search['status2']   = !empty($search['status2']) ? $search['status2'] : null;
    $search['important'] = !empty($search['important']) ? $search['important'] : null;

    $search['report'] = '看多機記録';

    /*-- 更新用パラメータ ---------------------------------------*/

    /*-- その他パラメータ ---------------------------------------*/

    // ページャー
    $page = h(filter_input(INPUT_GET, 'page'));


    /* ===================================================
     * イベント前処理(更新用配列作成、入力チェックなど)
     * ===================================================
     */

    /* -- 更新用配列作成 ----------------------------------------*/


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
    $gnrList = getCode('帳票一括確認');

    /* -- 利用者情報 ---------------------------*/
    $userList = getData('mst_user');

    /* -- スタッフ情報 ---------------------------*/
    $staffList = getData('mst_staff');

    /* -- 事業所情報 ---------------------------*/
    $ofcList = getData('mst_office');

    /* -- 看多機記録 -------------------------------*/
    $where  = array();
    if (!empty($search['start_day'])) {
        $where['create_date >='] = $search['start_day'];
    }
    if (!empty($search['end_day'])) {
        $where['create_date <='] = $search['end_day'];
    }
    $temp = getData('doc_kantaki', $where);
    foreach ($temp as $idx => $val) {
        $unqId              = $val['unique_id'];
        $dat                = $val;
        $dat['report_name'] = '看多機記録';
        $dat['edit_url']    = '/report/kantaki/index.php?id=' . $val['unique_id'] . '&user=' . $val['user_id'];
        $dat['copy_url']    = '/report/kantaki/index.php?copy=' . $val['user_id'];
        $userInfo           = isset($userList[$val['user_id']]) ? $userList[$val['user_id']] : array();
        $staffInfo          = '';//isset($staffList[$val['staff_id']]) ? $staffList[$val['staff_id']] : array();
        $dat['user_name']   = !empty($userInfo) ? $userInfo['last_name'] . ' ' . $userInfo['first_name'] : null;
        $dat['user_kana']   = !empty($userInfo) ? $userInfo['last_kana'] . ' ' . $userInfo['first_kana'] : null;
        $dat['person_name'] = !empty($staffInfo) ? $staffInfo['last_name'] . ' ' . $staffInfo['first_name'] : null;

        // 氏名カナ
        if (!empty($search['kana'])) {
            if (mb_strpos($dat['user_kana'], $search['kana']) === false) {
                continue;
            }
        }

        // ステータス完成
        if (isset($search['status1']) && isset($search['status2']) === false) {
            if ($dat['status'] !== '完成') {
                continue;
            }
        }

        // ステータス未完成
        if (isset($search['status1']) === false && isset($search['status2'])) {
            if ($dat['status'] !== '作成中' && !empty($dat['status'])) {
                continue;
            }
        }

        // 重要フラグ
        if (isset($search['important'])) {
            if ($dat['important'] !== '重要') {
                continue;
            }
        }

        $tgtData[$unqId]    = $dat;
    }

    /* -- ソート処理 -----------------------------*/
    krsort($tgtData);

    /* -- その他 --------------------------------------------*/

    // ページャー
    $dispData = getPager($tgtData, $page, $line);

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
