<?php
/**
 * Created by PhpStorm.
 * User: Lany
 * Date: 2020/5/11
 * Time: 下午4:07
 */
function curl($url, $post = array(), $arr_return = true)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    $return_str = curl_exec($curl);
    curl_close($curl);
    if ($arr_return) {
        return json_decode($return_str, true);
    } else {
        return $return_str;
    }
}
