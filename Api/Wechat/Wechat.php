<?php

/**
 * @ Api file Wechat.php
 */
namespace Api\Wechat;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatHttp as WechatHttp;

use Entity;

class Wechat extends WechatAPI {
    /**
     * 微信入口api
     * @route /api/wechat/{url}
     * @access
     */
    public static function api($request) {
        //打印调试
        $w_get = $request->get->getParameters();
        $w_post = file_get_contents('php://input');
        logger()->debug("Weixin Get data: ".var_export($w_get,true));
        logger()->debug("Weixin Post xml data: ".var_export($w_post,true));
        /** end **/
        
        self::$wxconfig = variable()->get('wxconfig');
        $back = self::handleEvent();
        return new Response($back);
    }
    
    /*
     * 事件分发
     */
    public static function eventDispatch() {
        $xml   = @simplexml_load_string(self::$requestxml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        $xml_array = WechatHttp::extractXML($xml);
        logger()->debug("to send back request is ".var_export($xml_array,true));
        $event = strtolower($xml_array['MsgType']);
        if ($event == 'event') {
            switch (strtoupper($xml_array['Event'])) { 
                case 'LOCATION':
                    $method = 'onReportLocation';
                    break;
                default:
                    $method = 'on' . ucfirst($xml_array['Event']);
                    break;
            }
        } elseif (strpos('text,image,voice,vedio,location,link', $event) !== false) {
            $method = 'on' . ucfirst($event);
        } else {
            $method = 'unknown';
        }
        
        return self::$method($xml_array);
    }
    
    /*
     * 事件调度
     */
    public static function handleEvent() {
        $check = self::checkAccess();
        if ($check === true) {       
            return self::eventDispatch();
        } else {
            return $check;
        }
    }
    
    //拿到上报地理位置时
    
    public static function onReportLocation($xml_array) {
        
        $array = array(
            'lng'   => $xml_array['Longitude'],
            'lat'   => $xml_array['Latitude'],
            'zoom'  => $xml_array['Precision'],
        );
        $openid = $xml_array['FromUserName'];
        //先转换为腾讯坐标
        $changexyurl = "http://apis.map.qq.com/ws/coord/v1/translate?locations=%s&type=1&key=%s";
        $request = array(
            'location' => $array['lat'].",".$array['lng'],
            'key'      => config()->get('tengxun_key')
        );
        $xy = file_get_contents(vsprintf($changexyurl,$request));
        $xy = json_decode($xy,true);
        if ($xy['status'] == 0) {
            $array['lng'] = $xy['locations'][0]['lng'];
            $array['lat'] = $xy['locations'][0]['lat'];
        }       
        db_update("wxuser")
            ->fields($array)
            ->condition("openid",$openid)
            ->execute();
        logger()->debug("接收到一个地理位置上报：".var_export($array,true));
        
    }
    
    //取消关注时
    public static function onUnsubscribe($xml_array) {
        session()->delete('wx_authentication');
        //更新关注状态
        db_update("wxuser")
            ->fields(array('subscribe'=>2))
            ->condition("openid",$xml_array['FromUserName'])
            ->execute();
            
        $output = array(
            'ToUserName'    => $xml_array['FromUserName'],
            'FromUserName'  => $xml_array['ToUserName'],
            'CreateTime'    => time(),
            'MsgType'       => 'text',
            'Content'       => "感谢你曾经在我们这逗留过!",
        );
       
        return WechatHttp::response($output);
    }
    //关注时 主动回复消息
    public static function onSubscribe($xml_array) {
        $output = array(
            'ToUserName'    => $xml_array['FromUserName'],
            'FromUserName'  => $xml_array['ToUserName'],
            'CreateTime'    => time(),
            'MsgType'       => 'text',
            'Content'       => "谢谢关注我们的微信服务号!",
        );
        //写入、更新微信用户表
        $wxuser = WechatAPI::getUserInfo($xml_array['FromUserName']);
        $array = array(
            'username'  => $wxuser['nickname'],
            'nickname'  => $wxuser['nickname'],
            'openid'    => $wxuser['openid'],
            'city'      => $wxuser['city'],
            'province'  => $wxuser['province'],
            'country'   => $wxuser['country'],
            'headimgurl' => $wxuser['headimgurl'],
            'subscribe_time' => $wxuser['subscribe_time'],
            'language'  => $wxuser['language'],
            'sex'       => $wxuser['sex'],
            'subscribe' => $wxuser['subscribe'],
            'password'  => '',
            'unionid'   => '',
        );
        $olduser = db_select("wxuser","w")
            ->fields("w",array('wid'))
            ->condition("openid",$wxuser['openid'])
            ->execute()
            ->fetch();
        if ($olduser) {
            $array['wid'] = $olduser->wid;
            Entity\Wxuser\Wxuser::update(entity_request($array));
            logger()->debug("######### 有旧会员重新关注 is:".var_export($wxuser,true));
        }
        else {
            Entity\Wxuser\Wxuser::insert(entity_request($array));
            logger()->debug("######### 有新的微信会员关注 is:".var_export($wxuser,true));
        }
        
        return WechatHttp::response($output);
    }
    
    //收到输入关键字时
    public static function onText($xml_array) {
        /* 主动发送消息  未认证账号无法使用。
        $output = array(
            "touser"    => $xml_array['FromUserName'],
            "msgtype"   => "text",
            "text"      => array(
               "content" => "谢谢关注我们的微信服务号!【".$xml_array['Content']."】",
            ),
        );
        logger()->debug("######### Call Back array is:".var_export($output,true));
        $res = self::send($output);
        logger()->debug("#### weixin return ".$res[1]);
        */
        //被动回复消息
        
        $output = array(
            'ToUserName'    => $xml_array['FromUserName'],
            'FromUserName'  => $xml_array['ToUserName'],
            'CreateTime'    => time(),
            'MsgType'       => 'text',
            'Content'       => "谢谢关注我们的微信服务号!【".$xml_array['Content']."】",
        );
        $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($xml_array['FromUserName']);
        $array = array(
            'wid'     => $wxuser->wid,
            'content' => $xml_array['Content']
        );
        Entity\Message\Message::insert(entity_request($array));
        logger()->debug("######### Call Back array is:".var_export($output,true));
        return WechatHttp::response($output);
        
    }
    
    //扫码提示事件
    public static function onScancode_waitmsg($xml_array) {
        switch($xml_array['EventKey']) {
            case 'do_queue':    //扫码拿号
                self::doQueue($xml_array);
            break;
        }
    }
    
    //扫码拿号  暂不实现功能
    protected static function doQueue($xml_array) {
        $scanjson = json_decode($xml_array['ScanCodeInfo']['ScanResult'],true);
        logger()->debug("scanjson data is:".var_export($scanjson,true));
    }
    
}
