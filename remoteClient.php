<?php
require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use conf\Config;
use server\Remote;

//$query_param = [
//    'command' => 'callRemote',
//    'data' => [
//        'url' => 'http://120.78.190.34/nba/games',
//        'method' => 'POST',
//        'data' => json_encode(['tid' => '583ecdfb-fb46-11e1-82cb-f4ce4684ea4c', 'page' => 1, 'page_size' => 5]),
//        'headers' => ['Content-type: application/json']
//    ]
//];

$headers = [
    'Cookie:PDDAccessToken=WGTPOIE3ET5W62HZQLWYS2FBD7RMJZUTRQRD3HIIYLNDN3FPMUTA113548f',
];

/*$query_param = [
    'command' => 'callRemote',
    'platform' => 'pdd',
    'action' => 'check_order_status',
    'data' => [
        'url' => 'https://mobile.yangkeduo.com/order.html?order_sn=191231-393099674410852',
        'method' => 'GET',
        'data' => '',
        'headers' => $headers,
        'options' => [
            CURLOPT_COOKIE => 'PDDAccessToken=WGTPOIE3ET5W62HZQLWYS2FBD7RMJZUTRQRD3HIIYLNDN3FPMUTA113548f'
        ]
    ],
    'callback' => [
        'url' => 'http://bbs.ananazq.com/common/index/test',
        'method' => 'POST',
        'data' => 'id=1&name=remote'
    ]
];*/

/*$query_param = $query_param = [
    'command' => 'callRemote',
    'platform' => 'pdd',
    'action' => 'check_user_address',
    'data' => [
        'url' => 'https://mobile.yangkeduo.com/addresses.html',
        'method' => 'GET',
        'data' => '',
        'headers' => [
            "Connection:keep-alive",
            "Host:mobile.yangkeduo.com",
            "Upgrade-Insecure-Requests:1",
//            "Cookie: api_uid=rBUGYF186VioemUxmqTUAg==; _nano_fp=Xpd8Xpgxn5Uon0TjnT_wpkxyM1I21bMkpBYUCgz3; msec=1800000; rec_list_orders=rec_list_orders_fND6xd; chat_list_rec_list=chat_list_rec_list_tixjkH; pdd_user_id=4506088908608;PDDAccessToken=554HXW6HOFVWSUCQ7XMW766BO7WLJ6SGHREB3DCFSG3T6DMAGYWA1134041; ua=Openwave%2F+UCWEB7.0.2.37%2F28%2F999; webp=1; mlp-fresher-mix=mlp-fresher-mix_pLq7Bo; rec_list_order_detail=rec_list_order_detail_BbHRyy; rec_list_mall_bottom=rec_list_mall_bottom_H8lk2x; home_bottom=home_bottom_IP3Uc9; mall_main=mall_main_0okyEP"
        ],
        'options' => [
            CURLOPT_COOKIE => "api_uid=rBUGYF186VioemUxmqTUAg==; _nano_fp=Xpd8Xpgxn5Uon0TjnT_wpkxyM1I21bMkpBYUCgz3; msec=1800000; rec_list_orders=rec_list_orders_fND6xd; chat_list_rec_list=chat_list_rec_list_tixjkH; pdd_user_id=4506088908608;PDDAccessToken=554HXW6HOFVWSUCQ7XMW766BO7WLJ6SGHREB3DCFSG3T6DMAGYWA1134041; ua=Openwave%2F+UCWEB7.0.2.37%2F28%2F999; webp=1; mlp-fresher-mix=mlp-fresher-mix_pLq7Bo; rec_list_order_detail=rec_list_order_detail_BbHRyy; rec_list_mall_bottom=rec_list_mall_bottom_H8lk2x; home_bottom=home_bottom_IP3Uc9; mall_main=mall_main_0okyEP"
        ]
    ],
    'callback' => [
        'url' => 'http://www.pddnew.com/common/index/test',
        'method' => 'POST',
        'data' => 'id=1&name=remote'
    ]
];*/

$query_param = [
    'command' => 'callRemote',
    'data' => [
        'url' => 'http://www.pddnew.com/v3/account/edit',// . '?' . urldecode(http_build_query(['secret' => 'Iuq627IWYZItAVgYH276tpu1TyPlvFBjDXIzkw4I9B+\/6XxtG8aJBXdZG\/\/R31ZBr1d6T7cy\/6HJ5qK9la+CgpagOZ0TJFM4m3JS74\/DlH+7h+mOVgzKwWZmKvR6cg48\/Yy9DtoV4\/pIKVgb1lpPrZtJKRkN8XnYbohN9s\/jsS28bPbC7wWx9yrVX4n0voRXft035ksBm8e9iqgjUagNVUIeV+FISuO4wLLKkFb\/UT9WR5n4LNaNNX7hnFrdF+qQRo29HFLFUZYRBn2wNTPM\/3WDNpx4Z\/+lpYk6\/xIQgFcUzxvg\/zSZno1ltp\/FGuCbJaVICTKq6vdbSfr1GwD2lwdrs3QImHzdofYSe2Eq4vHOQWyGXCLTPfIJrZV7jDUI\/HpVobzeZCOKJFLh+gWRLRrcJfWXo\/A7XrtU6QMsRiGrDbWPoOWVHA1nGBR3p8udUQ5NYgNR30raNr2fPLNimmsyX8KeEwMP1AJONHmty+ctXD64v8w01wBzHr3TJ\/iSg+iKorriUzxfUkH\/uIjYpgClRN9SOqPnmPq64VOV5w75HAP+WCJHIT3+0xbljo+QdEJdUGpEgup73TpwHQ0OcPEgwHrUyw9ZvJvLBqwp2oCDexvn9zISe5tNyrclYD1O+P2Mk4MPE\/MOsjhLV71+vRwVVPzVhQgvKJcxlEk+Zg0='])),
        'method' => 'POST',
        'data' => 'secret=' . urlencode('Iuq627IWYZItAVgYH276tpu1TyPlvFBjDXIzkw4I9B+\/6XxtG8aJBXdZG\/\/R31ZBr1d6T7cy\/6HJ5qK9la+CgpagOZ0TJFM4m3JS74\/DlH+7h+mOVgzKwWZmKvR6cg48\/Yy9DtoV4\/pIKVgb1lpPrZtJKRkN8XnYbohN9s\/jsS28bPbC7wWx9yrVX4n0voRXft035ksBm8e9iqgjUagNVUIeV+FISuO4wLLKkFb\/UT9WR5n4LNaNNX7hnFrdF+qQRo29HFLFUZYRBn2wNTPM\/3WDNpx4Z\/+lpYk6\/xIQgFcUzxvg\/zSZno1ltp\/FGuCbJaVICTKq6vdbSfr1GwD2lwdrs3QImHzdofYSe2Eq4vHOQWyGXCLTPfIJrZV7jDUI\/HpVobzeZCOKJFLh+gWRLRrcJfWXo\/A7XrtU6QMsRiGrDbWPoOWVHA1nGBR3p8udUQ5NYgNR30raNr2fPLNimmsyX8KeEwMP1AJONHmty+ctXD64v8w01wBzHr3TJ\/iSg+iKorriUzxfUkH\/uIjYpgClRN9SOqPnmPq64VOV5w75HAP+WCJHIT3+0xbljo+QdEJdUGpEgup73TpwHQ0OcPEgwHrUyw9ZvJvLBqwp2oCDexvn9zISe5tNyrclYD1O+P2Mk4MPE\/MOsjhLV71+vRwVVPzVhQgvKJcxlEk+Zg0='),
        'headers' => [
            'Content-Type:application/x-www-form-urlencoded'
        ]
    ]
];

$client = Remote::getInstance();
try {
    $client->connect(Config::REMOTE_CLIENT_HOST, Config::REMOTE_PORT, Config::REMOTE_CLIENT_TIMEOUT);
    $client->callRemote($query_param);
} catch (Exception $e) {
    echo $e->getMessage();
} finally {
    $client->close();
    echo 'done';
}
