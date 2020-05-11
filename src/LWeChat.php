<?php
/**
 * Created by PhpStorm.
 * User: Lany
 * Date: 2020/5/11
 * Time: 下午3:56
 */
namespace Lany\LWeChat;

use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LWeChat
{
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config->get('l_wechat');
    }

    /**
     * 微信公众平台Signature
     * @param Request $request
     */
    public function api(Request $request)
    {
        $echoStr = $request->echostr;
        if ($echoStr && $this->checkSignature($request)) {
            exit($echoStr);
        }
    }

    //检查签名
    private function checkSignature($request)
    {
        $signature = $request->signature;
        $timestamp = $request->timestamp;
        $nonce = $request->nonce;
        $token = $this->config['app_token'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getOpenid()
    {
        if (request()->getClientIp() == "127.0.0.1") {
            $openid = "local";
            session(["openid" => $openid]);
            return redirect($this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        };
        $request = request();
        if (!$request->get("code")) {
            /**
             * 回调code
             */
            /*$oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect';*/

            $oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=%s#wechat_redirect';

            $oauthUrl = sprintf($oauthUrl, $this->app_id, urlencode($this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), md5(time()));
            return redirect($oauthUrl);
        } else {
            /**
             * 通过code换取openid
             */
            $oauthUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
            $oauthUrl = sprintf($oauthUrl, $this->config['app_id'], $this->config['app_secret'], $request->get("code"));
            $callback = curl($oauthUrl);
            if (!isset($callback['openid'])) {
                dd("获取OPENID失败，请重试！");
            }

            $openid = $callback['openid'];
            session(["openid" => $openid]);
            Cache::put("weixin_access_token", $callback['access_token'], 3000);
            return redirect($this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }
    }

    public function getUserInfo($openid) {
        $access_token = Cache::get('weixin_access_token');
        /*$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';*/

        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

        return curl($url);
    }


    /**
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $access_token = cache('access_token');
        if (!$access_token) {
            $callback = curl('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->config['app_id'] . '&secret=' . $this->config['app_secret']);
            if (!isset($callback['access_token'])) {
                dd("获取ACCESS_TOKEN失败，请重试！");
            }
            $access_token = $callback['access_token'];
            Cache::put("access_token", $access_token, 60);
        }
        return $access_token;
    }


    /**
     * 通过access_token换取jsapi_ticket，由于微信限制，access_token将于7200秒之后失效，所以拿到之后需进行缓存或者写入数据库
     */
    public function getJsApiTicket()
    {
        $ticket = "";
        do {
            $ticket = cache('weixin_ticket');
            if (!empty($ticket)) {
                break;
            }
            $token = $this->getAccessToken();
            if (empty($token)) {
                dd("获取access token失败");
                break;
            }
            $url = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi", $token);
            $callback = curl($url);
            $ticket = $callback['ticket'];
            Cache::put("weixin_ticket", $ticket, 60);
        } while (0);
        return $ticket;
    }

    /**
     * 后台生成jsapi所需的配置参数，此动作无法通过前台完成
     */
    public function jsApiConfig()
    {
        if (request()->getClientIp() == "127.0.0.1") {
            $js_api_ticket = "";
            $js_api_nonce_str = "";
            $js_api_timestamp = time();
            $js_api_url = "";
            $js_api_signature = "";

            $data['js_api_ticket'] = $js_api_ticket;
            $data['js_api_nonce_str'] = $js_api_nonce_str;
            $data['js_api_timestamp'] = $js_api_timestamp;
            $data['js_api_url'] = $js_api_url;
            $data['js_api_signature'] = $js_api_signature;
            return $data;

        };
        $js_api_ticket = $this->getJsApiTicket(); //jsapi_ticket
        $js_api_nonce_str = $this->config['js_api_nonce_str']; //随机字符
        $js_api_timestamp = time(); //当前时间戳
        $js_api_url = $this->protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $js_api_signature = sha1(sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $js_api_ticket, $js_api_nonce_str, $js_api_timestamp, $js_api_url)); //四个变量按照字典序排列之后，进行sha1加密

        $data['js_api_ticket'] = $js_api_ticket;
        $data['js_api_nonce_str'] = $js_api_nonce_str;
        $data['js_api_timestamp'] = $js_api_timestamp;
        $data['js_api_url'] = $js_api_url;
        $data['js_api_signature'] = $js_api_signature;
        return $data;
    }

    public function testFun()
    {
        return $this->config;
    }

}
