<?php

//=====================================================================
// [ajax]指示書情報検索
//=====================================================================
try {
    /* ===================================================
     * 初期処理
     * ===================================================
     */

    /*--共通ファイル呼び出し-------------------------------------*/
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_start.php');

    /*--変数定義-------------------------------------------------*/
    $notice   = null;
    $sendData = array();

    /* ===================================================
     * 入力情報取得
     * ===================================================
     */

    // 検索場所
    $type = h(filter_input(INPUT_GET, 'type'));

    // 更新内容
    $upAry = filter_input(INPUT_GET, 'upAry', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $upAry = $upAry ? $upAry : array();

    $upDummy = filter_input(INPUT_GET, 'upDummy', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $upDummy = $upDummy ? $upDummy : array();


    /* ===================================================
     * イベント後処理(描画用データ作成)
     * ===================================================
     */
    /* -- データ取得 ----------------------------------------*/



    /* -- 検索結果反映 --------------------------------------*/

    // 主治医情報検索
    if ($type === 'doctor') {

        // 初期化
        $upAry['medical_institution'] = null;
        $upAry['doctor']              = null;
        $upAry['address']             = null;
        $upAry['tel1']                = null;
        $upAry['tel2']                = null;
        $upAry['fax']                 = null;

        // 入力内容がある時に検索実行
        if (!empty($upDummy['other_id']) || !empty($upAry['staff_id']) || !empty($upAry['care_kb']) || !empty($upAry['validate_start'])) {

            // マスタ取得
            $where = array();
            $where['delete_flg']        = 0;
            $where['user_id']           = $upDummy['other_id'];
            $where['staff_id']          = $upAry['staff_id'];
            if ($upAry['care_kb'] == '訪問看護') {
                $where['care_kb']       = '一般';
            } elseif ($upAry['care_kb'] == '精神科訪問看護') {
                $where['care_kb']       = '精神';
            }
            $where['direction_end >= '] = $upAry['validate_start'];
            $orderBy = 'unique_id DESC';
            if (!empty($upAry['validate_end'])) {
                $where['direction_start <= '] = $upAry['validate_end'];
            }
            $temp = select('doc_instruct', '*', $where, $orderBy);

            // 主治医情報を補完
            if (isset($temp[0])) {

                // 主治医、医療機関名称、所在地
                $upAry['doctor']   = !empty($temp[0]['doctor'])
                        ? $temp[0]['doctor']
                        : null;
                $upAry['medical_institution'] = !empty($temp[0]['hospital'])
                        ? $temp[0]['hospital']
                        : null;
                $upAry['address']  = !empty($temp[0]['address1'])
                        ? $temp[0]['address1']
                        : null;

                // 電話番号１、電話番号２、ＦＡＸ
                $upAry['tel1'] = !empty($temp[0]['tel1'])
                        ? $temp[0]['tel1']
                        : null;
                $upAry['tel2'] = !empty($temp[0]['tel2'])
                        ? $temp[0]['tel2']
                        : null;
                $upAry['fax'] = !empty($temp[0]['fax'])
                        ? $temp[0]['fax']
                        : null;

            } else {
                $notice = '検索条件に合致しません';
            }
        }
    }

    /* -- データ送信 ----------------------------------------*/
    $sendData = array();
    foreach ($upAry as $key => $val) {
        $sendData['upAry[' . $key . ']'] = $val;
    }
    // -----------------------------------------------------------

    if ($sendData) {
        echo sprintf("setMultiValue(%s);", jsonEncode($sendData));
    }

    // メッセージ送信
    if ($notice) {
        echo sprintf("noticeModal(%s);", jsonEncode($notice));
    }
    exit;

    /* ===================================================
     * 例外処理
     * ===================================================
     */
} catch (Exception $e) {
    debug($e);
    exit;
    $_SESSION['err'] = !empty($err) ? $err : array();
    header("Location:" . ERROR_PAGE);
    exit;
}
