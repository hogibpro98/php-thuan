<?php

//=====================================================================
// [ajax]郵便番号存在チェック
//=====================================================================
try {
    /* ===================================================
     * 初期処理
     * ===================================================
     */

    /* --共通ファイル呼び出し------------------------------------- */
    require_once($_SERVER['DOCUMENT_ROOT'] . '/common/php/com_start.php');

    /* --変数定義------------------------------------------------- */
    $notice = null;
    $searchPostNo = "";

    /* ===================================================
     * 入力情報取得
     * ===================================================
     */

    // 検索場所
    $postNo = h(filter_input(INPUT_POST, 'post_no'));

    if (mb_strlen($postNo) === 8 || mb_strlen($postNo) === 7) {
        $post1 = substr($postNo, 0, 3);
        $post2 = substr($postNo, -4);
        $searchPostNo = $post1 . "-" . $post2;
    }

    /* ===================================================
     * イベント後処理(描画用データ作成)
     * ===================================================
     */
    /* -- データ取得 ---------------------------------------- */

    // 郵便番号検索
    if ($searchPostNo) {
        // マスタ取得
        $where = array();
        $where['delete_flg'] = 0;
        $where['post'] = $searchPostNo;
        $temp = select('mst_area', '*', $where);
        if (!isset($temp[0])) {
            $notice = '入力された郵便番号は存在しません';
        }
    } else {
        //    $notice = '入力された郵便番号は存在しません';
    }
    // -----------------------------------------------------------
    // メッセージ送信
    //    echo sprintf("noticeModal(%s);",jsonEncode($notice));
    echo $notice;
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
