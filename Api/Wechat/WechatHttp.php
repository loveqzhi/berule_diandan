<?php 
namespace Api\Wechat;

class WechatHttp {
    
    /*
     * 获取请求数据
     */
    public static function getRequest($key = null) {
        $data  = file_get_contents('php://input');
        $xml   = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        $array = self::extractXML($xml);
        if ($key) {
            return isset($array[$key]) ? $array[$key] : false;
        } else {
            return $array;
        }
    }
    
    /*
     * 设置事件响应数据
     */
    public static function response($array, $htmlentity = true) {
        header('Content-Type: text/xml;charset=utf-8');
        return '<xml>' . self::buildXML($array, $htmlentity) . '</xml>';
    }
    
    /*
     * CURL
     */
    public static function curl($url, $headers = array(), $params = array()) {
        $header = array();
        $headers += array('Expect' => '');
        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }
        $ch = curl_init();
        $option = array(
            CURLOPT_URL             => $url,
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_HEADER          => true,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_TIMEOUT         => 30,
        );
        if (count($params)) {
            $option[CURLOPT_POST] = true;
            $option[CURLOPT_POSTFIELDS] = $params;
        }
        if (stripos($url, 'https') === 0) {
            $option[CURLOPT_SSL_VERIFYPEER] = false;
            $option[CURLOPT_SSL_VERIFYHOST] = false;
        }
        curl_setopt_array($ch, $option);
        $content = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $content = "HTTP/1.1 501 ERROR\r\n\r\n";
        }
        curl_close($ch);

        return explode("\r\n\r\n", $content, 2);
    }

    //解析xml
    public static function extractXML($xml) {
        if ($xml === false) {
            return false;
        }
        if (!($xml->children())) {
            return (string) $xml;
        }
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if (count($xml->$name) == 1) {
                $element[$name] = self::extractXML($child);
            } else {
                $element[][$name] = self::extractXML($child);
            }
        }
        return $element;
    }
    
    //拼装XML
    public static function buildXML($array, $htmlentity = true) {
        $output = '';
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $k = 'item';
            }
            if (is_scalar($v)) {
                $v = preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/u", '', $v);
                if ($htmlentity) {
                    $output .= "<$k><![CDATA[" . str_replace('&amp;','&',htmlentities($v, ENT_QUOTES, 'UTF-8')) . "]]></$k>";
                } else {
                    $output .= "<$k>" . $v . "</$k>";
                }
            } else {
                $output .= "<$k>" . self::buildXML((array) $v) . "</$k>";
            }
        }

        return $output;
    }

}
