<?php
/**
 * @ file Queue.php 排队
 */
namespace Wechat\Queue;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Entity;

class Queue {
    
    
    /**
     * 生成排队号
     * @route /wechat/queue/save
     * @access
     * @return json 
     */
    public static function save($request) {
        $post_data = $request->post->getParameters();
        $wx_authen = session()->get('wx_authentication');
        if ($post_data) {
            if ($post_data['appsecret'] != md5($post_data['shop_id'].variable()->get('wxconfig')['appsecret'])) {
                 return new Response(json_encode(
                                array('status'=>'error','msg'=>'非法提交')),'200',
                                array('Content-Type'=>'application/json'));
            }
            $shop = Entity\Shop\Shop::load(entity_request(array('id'=>$post_data['shop_id'])));
            $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
            logger()->debug("wxuser data is:".var_export($wxuser,true));       
            $lastqueue = db_select("queue","q")
                            ->fields("q")
                            ->condition("date",date('Ymd'))
                            ->condition("shop_id",$post_data['shop_id'])
                            ->orderBy("id","DESC")
                            ->execute()
                            ->fetch();
            logger()->debug("lastqueue data is:".var_export($lastqueue,true));      
            $queue = array(          
                'date'      => date('Ymd'),
                'shop_id'   => $post_data['shop_id'],
                'appid'     => variable()->get('wxconfig')['appid'],
                'headimgurl'=> $wxuser->headimgurl,
                'number'    => empty($lastqueue)?'1':($lastqueue->number+1),
                'human'     => $post_data['human'],
                'nickname'  => $wxuser->nickname,
                'phone'     => '',
            );
            logger()->debug("queue data is:".var_export($queue,true));
            Entity\Queue\Queue::insert(entity_request($queue));
            $output = array(
                "touser"    => $wx_authen['openid'],
                "template_id" => "cm9YJrX5mGOwniBnVu-CWjL2RF91YxAXnaiapzzw6SM",
                "topcolor" => "#FF0000",
                "data" => array(
                    'first' => array(
                        'value' => '恭喜你微信预约排队成功！',
                        'color' => '#173177',
                    ),
                    'keyword1' => array(
                        'value' =>  $queue['number'],
                        'color' => '#173177',
                    ),                 
                    'keyword2' => array(
                        'value' =>  date('Y-m-d H:i:s'),
                        'color' => '#173177',
                    ),
                    'keyword3' => array(
                        'value' => '7分钟左右',
                        'color' => '#173177',
                    ),
                    'keyword4' => array(
                        'value' =>  '2',
                        'color' => '#173177',
                    ),
                    'remark' => array(
                        'value' =>  '如有疑问请到 '.$shop->name.' 柜台咨询',
                        'color' => '#173177',
                    ),
                ),
            );
            
            $res = WechatAPI::sendTemplate($output);
            
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
        }
        else {
            header("Location: /wechat/index");
        }
    }
    
    /**
     * 排队选择用餐人数确认
     * @route /wechat/queue/confirm
     * @access
     * @return json 
     */
    public static function confirm($request) {
        $gets = $request->get->getParameters();
        $wx_authen = session()->get('wx_authentication');
        $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        if ($wxuser && $wxuser->subscribe==1) {
            if ($gets['appid'] != variable()->get('wxconfig')['appid'] 
                || $gets['appsecret'] != md5($gets['shop_id'].variable()->get('wxconfig')['appsecret'])
                || empty($gets['shop_id'])) {
                //验证是否来自于我们制定的二维码
                header("Location: /wechat/index");
            }
            return new Response(theme()->render('queue-confirm.html',$gets));
        }
        else {
            //echo "<h1>请先关注我们的微信公众号 'URMS'</h1>";exit;
            return new Response(theme()->render('unsubscribe.html',array()));
        }
    }
    

    
}