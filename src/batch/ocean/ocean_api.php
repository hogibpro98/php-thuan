<?php

//=====================================================================
// オーシャン連携API
//=====================================================================
/* ===================================================
 * 初期処理
 * ===================================================
 */
/*--共通ファイル呼び出し-------------------------------------*/
require_once(dirname(__FILE__) . '/../../common/php/com_server.php');
require_once(dirname(__FILE__) . '/../../common/php/com_ini.php');
require_once(dirname(__FILE__) . '/../../common/php/com_calendar.php');
require_once(dirname(__FILE__) . '/../../common/php/func_encode.php');
require_once(dirname(__FILE__) . '/../../common/php/func_array.php');
require_once(dirname(__FILE__) . '/../../common/php/func_db.php');
require_once(dirname(__FILE__) . '/../../common/php/func_file.php');
require_once(dirname(__FILE__) . '/../../common/php/func_trim.php');
require_once(dirname(__FILE__) . '/../../common/php/func_get.php');
require_once(dirname(__FILE__) . '/../../common/php/func_set.php');
require_once(dirname(__FILE__) . '/../../common/php/func_pager.php');
require_once(dirname(__FILE__) . '/../../common/php/com_user.php');
require_once(dirname(__FILE__) . '/../../common/php/func_mail.php');
require_once(dirname(__FILE__) . '/../../common/php/func_pdf.php');
require_once(dirname(__FILE__) . '/../../common/php/func_excel.php');
require_once(dirname(__FILE__) . '/../../common/php/func_csv.php');
require_once(dirname(__FILE__) . '/../../common/php/func_curl.php');
require_once(dirname(__FILE__) . '/../../common/plugin/httpful/vendor/autoload.php');

use Httpful\Request;

/*--変数定義-------------------------------------------------*/
try {

    // 初期化
    $param     = array();
    $headers   = array();
    $response  = array();
    $emplData  = array();
    $data      = array();
    $resArr    = array();

    // API接続情報
    $AUTH_API  = '/api/vendor/auth';
    $EMPL_API  = '/api/vendor/employees/layer';

    // APIの1回に取得するデータ件数
    $PAGE_SIZE = 1000;

    $mode = 'PRD';

    // API接続先情報
    $CONNECT = array(

        // 検証環境
        'STG' => array(
            'base_url' => 'https://staging-api.yst-org.com',
            'app_id' => 'AmbVPHI6og2ahijeTM3s6bWMyXOM3i4hgaSvbI8sPHdCy1qp42JUHK8od5M3',
            'app_secret' => 'MD27c5Vt9xpCI1xaIfK6QhDoQLSh6R5b6BDkzFLwsNvk6sUdyXaQNnAL1ThXXLaQuox3BC',
        ),
        // 本番環境
        'PRD' => array(
            'base_url' => 'https://api.yst-org.com',
            'app_id' => '2ECm9fzy0vn7iiFpCegGVhQFLzcZTbAsBRW1fU1YBrNigDcuJCEHIN2qaUBw',
            'app_secret' => 'GujvzPUmvXEAJoOsDZdQScq4l3EFUOrwaCIx8e5buifbiETO4IwxjM1N0UV9as6oxlDxUJ',
        ),
    );

    echo sprintf('[%s]ocean_api:start.' . PHP_EOL, date('Y-m-d H:i:s'));

    //--------------------------------
    // AUTH APIに接続
    //--------------------------------
    $authData = null;
    $param = array(
        'app_id' => $CONNECT[$mode]['app_id'],
        'app_secret' => $CONNECT[$mode]['app_secret']
    );
    $headers = array("Content-Type" => "application/json");
    $response = Request::post($CONNECT[$mode]['base_url'] . $AUTH_API)->addHeaders($headers)->body(json_encode($param))->send();
    $resArr = json_decode($response);
    // レスポンス判定
    if (!empty($resArr) && array_key_exists('data', $resArr)) {
        $authData = $resArr->data;
    } else {
        exit;
    }

    //--------------------------------
    // 従業員情報取得API実行
    //--------------------------------
    // 総件数を取得するために一度叩く
    $total = 0;
    if (empty($authData)) {
        exit;
    }
    $queryString = http_build_query([
        'date'      => $authData->expired_at,
        'get_all'   => false,
        'page_size' => 1,
        'page'      => 0
    ]);
    $headers = array("token" => $authData->token);
    $response = Request::get($CONNECT[$mode]['base_url'] . $EMPL_API . '/?' . $queryString)->addHeaders($headers)->send();
    $resArr = json_decode($response);
    if (array_key_exists('data', $resArr)) {
        $total = $resArr->total;
    }

    echo sprintf('[%s]ocean_api:employees tatal count.[%d]' . PHP_EOL, date('Y-m-d H:i:s'), $total);

    // ループ回数をセット
    $loopNum = ceil($total / $PAGE_SIZE);
    for ($i = 1; $i <= $loopNum; $i++) {

        echo sprintf('[%s]ocean_api:start get employees.[%d]' . PHP_EOL, date('Y-m-d H:i:s'), $i);

        // pageSize分従業員情報取得APIを実行
        $queryString = http_build_query([
            'date'      => $authData->expired_at,
            'get_all'   => false,
            'page_size' => $PAGE_SIZE,
            'page'      => $i
        ]);
        $headers = array("token" => $authData->token);
        $response = Request::get($CONNECT[$mode]['base_url'] . $EMPL_API . '/?' . $queryString)->addHeaders($headers)->send();
        $resArr = json_decode($response);
        if (array_key_exists('data', $resArr)) {
            $emplData = array_merge($emplData, $resArr->data);
        }
        echo sprintf('[%s]ocean_api:end get employees.[%d]' . PHP_EOL, date('Y-m-d H:i:s'), $i);
    }
    // 従業員一覧取得
    $staffCdList = array();
    $staffList = getData('mst_staff');
    foreach ($staffList as $id => $val) {
        $code = $val['staff_id'];
        $staffCdList[$code] = $val;
    }

    // 拠点一覧取得
    $placeList = getData('mst_place');

    // 事業所一覧取得
    $officeList = getData('mst_office');

    // 更新配列作成
    $upData = array();
    $upStaffOffices = array();
    foreach ($emplData as $idx => $staffData) {

        $license2 = array();
        $name     = array();
        $kana     = array();
        $dat      = array();

        // 従業員コードが存在しない場合はスキップ
        $dat['staff_id'] = isset($staffData->code) ? $staffData->code : null;
        if (empty($dat['staff_id'])) {
            continue;
        }

        $keyId = isset($staffCdList[$dat['staff_id']]['unique_id']) ? $staffCdList[$dat['staff_id']]['unique_id'] : null;
        if ($keyId) {
            $dat['unique_id']   = $keyId;
        } else {
            $dat['password'] = 'yst' . $dat['staff_id'];
        }

        // 氏名、氏名カナ、性別、生年月日
        $name                   = explode('　', isset($staffData->name) ? $staffData->name : null);
        $kana                   = explode(' ', isset($staffData->name_kana) ? $staffData->name_kana : null);
        $dat['last_name']       = isset($name[0]) ? $name[0] : null;
        $dat['first_name']      = isset($name[1]) ? $name[1] : null;
        $dat['last_kana']       = isset($kana[0]) ? mb_convert_kana($kana[0], "K") : null;
        $dat['first_kana']      = isset($kana[1]) ? mb_convert_kana($kana[1], "K") : null;
        $dat['sex']             = !empty($staffData->gender) ? $staffData->gender === '2' ? '女性' : '男性' : '';
        $dat['birthday']        = isset($staffData->birthday) ? $staffData->birthday : null;

        // 住所、電話番号、メールアドレス
        $dat['address']         = isset($staffData->address) ? $staffData->address : null;
        $dat['tel']             = isset($staffData->personal_phone) ? $staffData->personal_phone : null;
        $dat['mail']            = isset($staffData->email) ? $staffData->email : null;

        // 第１役割、第２役割、職種
        $dat['role1']           = isset($staffData->positions[0]->name) ? $staffData->positions[0]->name : null;
        $dat['role2']           = isset($staffData->positions[1]->name) ? $staffData->positions[1]->name : null;
        $dat['job']             = isset($staffData->job->name) ? $staffData->job->name : null;

        // 所属拠点(第4階層)
        //$places[] = $staffData->department_4;

        // 所属事業所(第5階層)
        if (isset($staffData->department_5)) {
            $filterOffice = array_filter($officeList, function ($office) use ($staffData) {
                return $office['layer_code'] == $staffData->department_5->code;
            });
            if (!empty($filterOffice)) {

                $filterOffice = current($filterOffice);
                $filterPlace = array_filter($placeList, function ($place) use ($filterOffice) {
                    return $place['unique_id'] == $filterOffice['place_id'];
                });
                $upStaffOffices[] = array(
                    'staff_id' => $dat['staff_id'],
                    'place_id' => $filterOffice['place_id'],
                    'place_name' => (empty($filterPlace) ? '' : current($filterPlace)['name']),
                    'office1_id' => $filterOffice['unique_id'],
                    'office1_name' => $filterOffice['name'],
                );
            }
        }

        // 所属先情報（KANTAKIと名称が異なるので未設定）
        //        $upData['job']             = $staffData->accounting_department->name;
        //    $dat['place_code']      = isset($staffData->department_4->code) ? $staffData->department_4->code : NULL;
        //    $dat['place_name']      = isset($staffData->department_4->name) ? $staffData->department_4->name : NULL;

        // 自動車免許有無
        $dat['driving_license'] = isset($staffData->driving_license) ? $staffData->driving_license : null;

        // 請求用資格、保有資格
        $dat['license1']        = isset($staffData->degree1) ? $staffData->degree1 : null;
        $license2[]             = isset($staffData->degree1) ? $staffData->degree1 : null;
        $license2[]             = isset($staffData->degree2) ? $staffData->degree2 : null;
        $license2[]             = isset($staffData->degree3) ? $staffData->degree3 : null;
        $license2[]             = isset($staffData->degree4) ? $staffData->degree4 : null;
        $dat['license2']        = !empty($license2) ? implode('^', $license2) : null;

        // 入社日（KANTAKIにはない項目なので未設定）
        //    $dat['join_date']       = isset($staffData->join_date) ? $staffData->join_date : NULL;

        // 更新データ設定
        $upData[] = $dat;
    }

    if ($upData) {
        $loginUser = array();
        $loginUser['unique_id'] = 'ocean_api';

        // テーブルを更新
        $res = multiUpsert($loginUser, 'mst_staff', $upData);
        if (isset($res['err'])) {
            $err[] = 'システムエラーが発生しました';
            throw new Exception();
        }
    }
    // mst_staff_office
    if (!empty($upStaffOffices)) {

        foreach ($upStaffOffices as $key => &$upStaffOffice) {

            $staff = getData('mst_staff', array('staff_id' => $upStaffOffice['staff_id']));
            if (empty($staff)) {
                unset($upStaffOffices[$key]);
                continue;
            }
            $upStaffOffice['staff_id'] = current($staff)['unique_id'];
            $upStaffOffice['start_day'] = '1900-01-01';
            $upStaffOffice['end_day'] = '2999-12-31';
            $upStaffOffice['office2_id'] = '';
            $upStaffOffice['office2_name'] = '';

            $staffOffice = getData('mst_staff_office', array('staff_id' => $upStaffOffice['staff_id']));
            if (!empty($staffOffice)) {
                $staffOffice = current($staffOffice);
                $upStaffOffice['unique_id'] = $staffOffice['unique_id'];
                $upStaffOffice['start_day'] = $staffOffice['start_day'];
                $upStaffOffice['end_day'] = $staffOffice['end_day'];
                $upStaffOffice['office2_id'] = $staffOffice['office2_id'];
                $upStaffOffice['office2_name'] = $staffOffice['office2_name'];
            }
        }
        // テーブルを更新
        $res = multiUpsert($loginUser, 'mst_staff_office', $upStaffOffices);
        if (isset($res['err'])) {
            $err[] = 'システムエラーが発生しました';
            throw new Exception();
        }
    }

    ///* -- その他 --------------------------------------------*/
    echo sprintf('[%s]ocean_api:end.' . PHP_EOL, date('Y-m-d H:i:s'));
    exit;
    /* ===================================================
     * 例外処理
     * ===================================================
     */
} catch (Exception $e) {
    echo $e->getMessage();
    echo sprintf('[%s]ocean_api:end.' . PHP_EOL, date('Y-m-d H:i:s'));
    exit;
}
