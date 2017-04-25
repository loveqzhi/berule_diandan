<?php
/**
 * @ file Shop.php
 */
namespace Wechat\Shop;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Entity;
use Wechat;

class Shop {
    /**
     * 微信店铺列表首页
     * @route /wechat/shop
     * @access
     * @param int category
     * @param int dist_id
     * @param int city_id
     * @param int street_id
     * @return string
     */
    public static function search($request) {
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 10);
        $wx_authen = session()->get('wx_authentication');
        $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        $res['city_id']     = '101';                           //城市ID暂时写死了上海市，以后要根据用户所在城市定位
        $res['dist_id']     = $res['dist'] = ($request->get('dist')=='')? null : $request->get('dist');   //区ID
        $res['street']      = ($request->get('street')=='') ? null : $request->get('street');    //街道ID
        $res['category']    = ($request->get('category')=='')? null : $request->get('category');  //当前分类
        $res['name']        = ($request->get('name')=='')? null : $request->get('name');      //搜索名称

        if (!empty($res['name']) || !empty($res['dist_id']) || !empty($res['street']) 
            || empty($wxuser->lng) || empty($wxuser->lat)) {    //带 区 街 搜索 或者 无用坐标
            $search_arr = array(        
                'category'  => array('value' => $res['category']),
                'city'      => array('value' => $res['city_id']),
                'dist'      => array('value' => $res['dist_id']),
                'street'    => array('value' => $res['street']),
            );
            if (!empty($res['name'])) {
                $search_arr['name']   = array('value' => "%".db_like($res['name'])."%",'flag'=>'LIKE');
            }
            $request->setParameter('conditions',$search_arr);
            $res['list'] = Entity\Shop\Shop::search($request);
        }
        elseif(!empty($wxuser->lng) && !empty($wxuser->lat)) {  //离用户最近
            logger()->debug("开始计算用户坐标范围: ".time());
            $squares = self::returnSquarePoint($wxuser->lng,$wxuser->lat, 1);
            $query   = db_select('shop', 's')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('s', array('id'))
                        ->condition('status', 1);
            if ($res['category']) {
                $query->condition("category",$res['category']);
            }
            $query->condition("s.lat",$squares['right-bottom']['lat'],">")
                ->condition("s.lat",$squares['left-top']['lat'],"<")
                ->condition("s.lng",$squares['left-top']['lng'],">")
                ->condition("s.lng",$squares['right-bottom']['lng'],"<");
            
            $query->orderBy('id','DESC');
            $pager = $query->fetchPager();
            $ids = $query->execute()->fetchCol();
            $data  = Entity\Shop\Shop::loadMulti(entity_request(array('ids'=>$ids)));
            $res['list'] = array('data'=>$data, 'pager'=>$pager);
            logger()->debug("查询附件餐厅结束: ".time());
        }
        $sorts = array();
        //批量处理距离
        foreach($res['list']['data'] as $k=>$entity) {
            $maps[$k] = array('lng'=>$entity->lng,'lat'=>$entity->lat);
        }
        self::checkMaxRoute($maps);
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
        logger()->debug("sort array is:".var_export($sorts,true));
        array_multisort($sorts,SORT_ASC,$res['list']['data']);
        $res['shop_category'] = config()->get('shop_category'); //店铺分类
        $res['dists'] = Entity\City\City::getByPid(101);        //地区数据
        if ($res['dist_id'] == null) {
            if ($res['street']) {
                $res['dist_id'] = Entity\City\City::load(entity_request(array('id'=>$res['street'])))->pid;
            } else {
                $res['dist_id'] = $res['dists'][0]->id;
            }
        }
        logger()->debug("show search in here");
        
        if ($request->get('format') == 'json') {
            return new Response(json_encode(
                        array('status'=>'success','data' => $res['list']['data'],'pager'=>$res['list']['pager'])),'200',
                        array('Content-Type'=>'application/json'));
        } else {
            return new Response(theme()->render('shop-list.html',$res));
        }
    }
    
    //处理输出数据
    public static function adjust_data($entity,$request) {
        static $wxuser;
        if (!$wxuser) {
            $wx_authen = session()->get('wx_authentication');
            $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        }
        if (!empty($entity->lat) && !empty($entity->lng)) {
            /*
            $request->setParameter('origins',$wxuser->lat.",".$wxuser->lng);
            $request->setParameter('destinations',$entity->lat.",".$entity->lng);
            $result = Wechat\Tool\Tool::getRouteMatrix($request);
            $entity->distance = current($result)['distance']['text'];
            $entity->distance_value = current($result)['distance']['value'];
            */
            $entity->distance = '';
            $entity->distance_value = '';
            
        }
    }
    
    //百度查询距离
    public static function checkMaxRoute(&$maps) {
        static $wxuser;
        if (!$wxuser) {
            $wx_authen = session()->get('wx_authentication');
            $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        }
        $i = 0;
        $destinations = '';
        $destinationkey = array();      
        foreach($maps as $k => $map) {
            if (!isset($map['distance'])) {
                if(isset($map['lng']) && isset($map['lat']) &&  !empty($map['lng']) 
                && !empty($map['lat']) && $i < 5) {
                    $i++;
                    $destinations .= $map['lat'].",".$map['lng']."|";
                    $destinationkey[] = $k;
                } else {
                    break;
                }
            }
        }
        if ($destinations && !empty($destinationkey)) {
            $array = array(
                'origins'   => $wxuser->lat.",".$wxuser->lng,
                'destinations' => substr($destinations,0,-1),
            );
            $result = Wechat\Tool\Tool::getRouteMatrix(entity_request($array));
            foreach ($destinationkey as $dk => $k) {
                $maps[$k] = array(
                    'distance'  => $result[$dk]['distance']['text'],
                    'distance_value' => $result[$dk]['distance']['value'],
                );
            }
            $result = null;
        }
        if ($i!=0) {
            self::checkMaxRoute($maps);
        } 
           
    }
    
    /**
     * 微信店铺详情
     * @route /wechat/shop/detail/{id}
     * @access
     * @param int category
     * @return string
     */
    public static function detail($request) {
        
        $id = $request->route->getParameter('id');
        $res = array(           
            'shop' => Entity\Shop\Shop::load(entity_request(array('id'=>$id))),
            'signPackage' => WechatAPI::getSignPackage(),
        );

        return new Response(theme()->render('shop-detail.html',$res));
    }
    
    /**
     *计算某个经纬度的周围某段距离的正方形的四个点
     *
     *@param lng float 经度
     *@param lat float 纬度
     *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为1千米
     *@return array 正方形的四个点的经纬度坐标
    */
    public static function returnSquarePoint($lng, $lat, $distance = 1) {
        $lng = abs($lng);
        $lat = abs($lat);
        $earth_radius = 6371;//地球半径，平均半径为6371km
        $dlng =  2 * asin(sin($distance / (2 * $earth_radius)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance/$earth_radius;
        $dlat = rad2deg($dlat);
        return array(
                    'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
                    'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
                    'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
                    'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
                    );
    }
    
}