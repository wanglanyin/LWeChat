<?php
/**
 * Created by PhpStorm.
 * User: Lany
 * Date: 2020/5/11
 * Time: 下午4:14
 */
namespace Lany\LWeChat\Facade;

use Illuminate\Support\Facades\Facade;

class LWeChat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'l_wechat';
    }
}
