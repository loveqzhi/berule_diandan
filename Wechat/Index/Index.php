<?php
/**
 * @ file Index.php
 */
namespace Wechat\Index;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Wechat;
use Entity;

class Index {

    /**
     * 微信首页
     * @route /wechat/index
     * @access
     * @return string
     */
    public static function search($request) {      
        $wx_authen = session()->get('wx_authentication');
        $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        logger()->debug("wxuser is".var_export($wxuser,true));
        $res = array(
            'location' => Wechat\Tool\Tool::getAddresss(entity_request(array('location'=>$wxuser->lat.",".$wxuser->lng))),
            //'list' => Entity\Shop\Shop::search($request),
        );
        $squares = Wechat\Shop\Shop::returnSquarePoint($wxuser->lng,$wxuser->lat);
        logger()->debug("location is ".var_export($squares,true));
        $query   = db_select('shop', 's')
                    ->extend('Pager')->page(1)->size(16)
                    ->fields('s', array('id'))
                    ->condition('status', 1);
        $query->condition("s.lat",$squares['right-bottom']['lat'],">")
            ->condition("s.lat",$squares['left-top']['lat'],"<")
            ->condition("s.lng",$squares['left-top']['lng'],">")
            ->condition("s.lng",$squares['right-bottom']['lng'],"<");
        
        $query->orderBy('id','DESC');
        $pager = $query->fetchPager();
        $ids = $query->execute()->fetchCol();
        $data  = Entity\Shop\Shop::loadMulti(entity_request(array('ids'=>$ids)));
        $res['list'] = array('data'=>$data, 'pager'=>$pager);
        $sorts = array();
        
        //批量处理距离
        foreach($res['list']['data'] as $k=>$entity) {
            $maps[$k] = array('lng'=>$entity->lng,'lat'=>$entity->lat);
        }
        
        Wechat\Shop\Shop::checkMaxRoute($maps);
        foreach($res['list']['data'] as $k => $entity) {
            //self::adjust_data($entity,$request);
            if (isset($maps[$k]['distance']) && !empty($maps[$k]['distance'])) {
                $sorts[$k] = $maps[$k]['distance_value'];
                $entity->distance = $maps[$k]['distance'];
                $entity->distance_value = $maps[$k]['distance_value'];
            } else {
                $sorts[$k] = '';
                $entity->distance = $entity->distance_value = '';
            }
        }
        /*
        foreach($res['list']['data'] as $k => $entity) {
            Wechat\Shop\Shop::adjust_data($entity,$request);
            $sorts[$k] = $entity->distance_value;
        }
        */
        array_multisort($sorts,SORT_ASC,$res['list']['data']);
        
        return new Response(theme()->render('index.html',$res));
    }
    
    /**
     * 微信关注页面
     * @route /wechat/unsubscribe
     * @access
     * @return string
     */
    public static function unsubscribe($request) {

        return new Response(theme()->render('unsubscribe.html',array()));
    }
    
    /**
     * 微信签到
     * @route /wechat/sign
     * @access
     * @return string
     */
    public static function sign($request) {
        
        $res = array(
            'signPackage' => WechatAPI::getSignPackage(),
        );
        
        return new Response(theme()->render('sign.html',$res));
    }
    
}
