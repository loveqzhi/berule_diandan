<?php

/*
 * @ file Tool.php
 */

namespace Wechat\Tool;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;

class Tool {
    /**
     * 计算两个坐标距离
     * @route /wechat/getroutematrix
     * @access
     * @param str mode      模式 driving  walking
     * @param str origins   起点 (纬度,经度)
     * @param str destinations 终点 (纬度,经度) 多个地点用|分隔 最多五个
     * @return array [0] => Array(
     *                      [distance] => Array
     *                          (
     *                              [text] => 914米
     *                              [value] => 914
     *                          )
     *
     *                      [duration] => Array
     *                          (
     *                              [text] => 15分钟
     *                              [value] => 912
     *                          )
     *                  )
     *
     *              [1] => Array
     *                  (
     *                      [distance] => Array
     *                          (
     *                              [text] => 996米
     */
    public static function getRouteMatrix($request) {
        $url = "http://api.map.baidu.com/direction/v1/routematrix?output=json&mode=%s&origins=%s&destinations=%s&ak=%s";
        $array = array(
            'mode'          => $request->getParameter('mode','walking'),
            'origins'       => $request->getParameter('origins',''),
            'destinations'  => $request->getParameter('destinations',''),
            'ak'            => config()->get('baidu_ak')
        );
        $res = file_get_contents(vsprintf($url,$array));
        $res = json_decode($res,true);
        if ($res['status'] == 0) {
            return $res['result']['elements'];
        }
        else {
            return array();
        }
    }
    
    /**
     * 获取地址
     * @param str location 经纬度 lat,lng
     *
     */
    public static function getAddresss($request) {
        $url = "http://apis.map.qq.com/ws/geocoder/v1?output=json&location=%s&key=%s";
        $request = array(
            'location' => $request->getParameter('location'),
            'key'      => config()->get('tengxun_key')
        );
        logger()->debug("请求腾讯地图参数：".var_export($request,true));
        $res = file_get_contents(vsprintf($url,$request));
        $res = json_decode($res,true);
        logger()->debug("腾讯地图 ".var_export($res,true));
          
        if ($res['status'] == 0) {
            return $res;
        }
        else {
            return '';
        }
    }
     
}