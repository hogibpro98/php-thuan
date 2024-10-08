<?php

//=======================================================================
//   データ抽出関数群
//=======================================================================

/* =======================================================================
 * 特定文字数でのカット関数
 * =======================================================================
 *
 *   [使用方法]
 *      $res = trimStrWidth(①,②,③)
 *
 *      半角は1文字、全角は2文字分としてカウント
 *      保管文字列込みでの文字数で判断
 *      途中で切れる場合は限界文字数未満の文字数となる
 *
 *   [引数]
 *      ① 変換前の文字列
 *      ② 限界文字数
 *      ③ 補完文字列
 *
 *   [戻り値] 文字列
 *      変換した文字列情報を返却
 *
 * -----------------------------------------------------------------------
 */
function trimStrWidth($target = null, $value = 40, $completion = '･･･')
{
    return mb_strimwidth($target, 0, $value, $completion, 'UTF-8');
}
/* =======================================================================
 * 特定文字数での折り返し・改行関数
 * =======================================================================
 *
 *   [使用方法]
 *      $res = trimStrWidth(①,②)
 *
 *      半角は1文字、全角は2文字分としてカウント
 *
 *   [引数]
 *      ① 変換前の文字列
 *      ② 限界文字数
 *
 *   [戻り値] 文字列
 *      変換した文字列情報を返却
 *      行ごとに配列として格納
 *
 * -----------------------------------------------------------------------
 */
function strWrap($target = null, $value = 200)
{
    $res = array();

    $tempStr = $target;
    $tempStr = str_replace("\r\n", "\n", $tempStr);
    $tempStr = str_replace("\r", "\n", $tempStr);

    $tempAry = array();
    $tempAry = explode("\n", $tempStr);

    foreach ($tempAry as $ary) {
        $i = 0;
        do {
            $res[] = mb_substr($ary, $i * $value, $value, 'UTF-8');
            $i++;
        } while ((mb_strlen($ary, 'UTF-8') - $i * $value) > 0);
    }

    return $res;
}
/* =======================================================================
 * htmlタグ平文化
 * =======================================================================
 *
 *   [使用方法]
 *      $res = trimStrWidth(①,②)
 *
 *      半角は1文字、全角は2文字分としてカウント
 *
 *   [引数]
 *      ① 変換前の文字列
 *      ② 限界文字数
 *
 *   [戻り値] 文字列
 *      変換した文字列情報を返却
 *      行ごとに配列として格納
 *
 * -----------------------------------------------------------------------
 */
function stripTags($str, $allowStr = '', $search = '', $replace = '')
{
    $str = str_replace($search, $replace, $str);
    return strip_tags($str, $allowStr);
}
