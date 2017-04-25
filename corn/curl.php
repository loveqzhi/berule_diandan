<?php

/**
 * A basic CURL wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
**/
class Curl {
    
    /**
     * The file to read and write cookies to for requests
     *
     * @var string
    **/
    public $cookie_file;
    
    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
    **/
    public $follow_redirects = false;
    
    /**
     * An associative array of headers to send along with requests
     *
     * @var array
    **/
    public $headers = array();
    
    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
    **/
    public $options = array();
    
    /**
     * The referer header to send along with requests
     *
     * @var string
    **/
    public $referer;
    
    /**
     * The user agent to send along with requests
     *
     * @var string
    **/
    public $user_agent;
    
    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string
     * @access protected
    **/
    protected $error = '';
    
    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
    **/
    protected $request;
    
    /**
     *
     */
    protected $request_cookie = null;

    /**
     *
     * out time
     *
     */
    protected $_outtime = '120';
    
    /**
     *
     * @ var array proxy_url 代理地址 最好做 hosts指向
     */
    protected $proxy_url = array(
        '1' => 'http://final14.gotoip4.com/proxy.php?uuu=',
        '2' => 'http://final15.gotoip55.com/proxy.php?uuu=',
        '3' => 'http://final16.gotoip1.com/proxy.php?uuu=',
        '4' => 'http://final17.gotoip1.com/proxy.php?uuu=',
        '5' => 'http://final18.gotoip3.com/proxy.php?uuu=',
        '6' => 'http://final19.gotoip3.com/proxy.php?uuu=',
        '7' => 'http://final20.gotoip3.com/proxy.php?uuu=',
        '8' => 'http://final2.gotoip1.com/proxy.php?uuu=',
        '9' => 'http://final3.gotoip3.com/proxy.php?uuu=',
        '10' => 'http://final5.gotoip3.com/proxy.php?uuu=',
        '11' => 'http://final6.gotoip55.com/proxy.php?uuu=',
        '12' => 'http://final8.gotoip2.com/proxy.php?uuu=',
        '13' => 'http://final9.gotoip4.com/proxy.php?uuu=',
        '14' => 'http://final10.gotoip55.com/proxy.php?uuu=',
        '15' => 'http://final11.gotoip55.com/proxy.php?uuu=',
        '16' => 'http://final12.gotoip4.com/proxy.php?uuu=',
        '17' => 'http://final13.gotoip3.com/proxy.php?uuu=',
        '18' => 'http://final20141.gotoip2.com/proxy.php?uuu=',
    );

    /**
     * @ 使用哪一号代理地址
     * @ $proxy_number
     */
    protected $proxy_number = null;
    /**
     * Initializes a Curl object
     *
     * Sets the $cookie_file to "curl_cookie.txt" in the current directory
     * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)' otherwise
    **/
    function __construct($cookie_file='') {
        if ($cookie_file!=''){
            $this->cookie_file = $cookie_file ;
        }
        else{
            $this->cookie_file = getcwd() . '/curl_cookie.txt';
        }
        //$this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)';
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0';
    }
    
    /**
     * @ 返回当前可用代理地址
     * @ return int
     */
    function get_proxy_number($useurl){
        $min_time = '1';
        $uri = parse_url($useurl);
        $headers = array("Location: ".$uri['scheme']."://".$uri['host']);
        $headers += array(
            "Accept: text/html,application/xhtml+xml,application/xml",
            "Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,pt;q=0.4",
            "Connection: close",
            "User-Agent: Mozilla/5.0 Firefox/23.0",
        );
        $number = 0;
        foreach($this->proxy_url as $k => $url) {
            $url = str_replace('proxy_cz.php?uuu=','ping.php',$url);
            $start_time = microtime(true);
            $ch = curl_init();
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1100);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            if (strpos($result,'SUCCESS') !== false &&  (microtime(true)-$start_time) < 1) {
                echo "此次使用代理：" . $url . "\n";
                $number = $k;
                break;
            } else {
                echo "代理失效了 " . $url . "\n";
            }
            $result = $ch = null; 
        }  
        if ($number == 0) {
            echo "代理全不可用，使用服务器发起连接\n";
        }
        return $number;
    }
    
    /**
     * set_cookie_file
     *
     *
     * @return mixed
    **/
    function set_cookie_file($cookie_file = '') {
        $cookie_dir = "/tmp/cookies/" . date('Ymd') ."/";
        $this->mkdir_r($cookie_dir);
        if ($cookie_file != ''){
            $this->cookie_file = $cookie_dir . $cookie_file ;
            exec('echo "" > '.$this->cookie_file);
        }
        return $this;
    }
    
    /**
     * set_options
     * 
     * @param $key 
     * @param $value
    **/
    function set_options($key,$value){
        if (!empty($key) && !empty($value)) {
            $this->options += array(strtoupper($key) => $value);
        }
    }
    
    /**
     * get_options
     *
     *
    **/
    function get_options(){
        return $this->options;
    }
    /**
     * get_cookie_file
     * 
     *
     *
     * @return string
    **/
    function get_cookie_file(){
        return $this->cookie_file;
    }
    
    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse object
    **/
    function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }
    
    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
    **/
    function error() {
        return $this->error;
    }
    
    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse
    **/
    function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }
    
    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse
    **/
    function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }
    
    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse|boolean
    **/
    function post($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }
    
    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse|boolean
    **/
    function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }
    
    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse|boolean
    **/
    function request($method, $url, $vars = array()) {
        //使用代理
        /*
        if (!isset($this->proxy_number)) {  //计算最优链接
            $this->proxy_number = $this->get_proxy_number($url);           
        }
		if($this->proxy_number !== 0){
			$url = $this->proxy_url[$this->proxy_number] . rawurlencode($url); 
		}
        */
        $url = $this->proxy_url[rand(1,18)] . rawurlencode($url);   //使用代理了
        
        $this->error = '';
        $this->request = curl_init();
        //新增&& $method != 'POST'否则不支持上传文件
        if (is_array($vars)){
            $vars = http_build_query($vars, '', '&');
        }
        
        $this->set_request_method($method);
        $this->set_request_options($url, $vars);
        $this->set_request_headers();
        $response = curl_exec($this->request);
        
        if ($response) {
            $response = new CurlResponse($response);
            if ($response->headers && isset($response->headers['Set-Cookie'])) {
                $this->request_cookie = $response->headers['Set-Cookie'];
                //log_message("debug","cookie is ".$this->request_cookie);
            }
        } else {
            $this->error = curl_errno($this->request).' - '.curl_error($this->request);
        }
        
        curl_close($this->request); 
        
        return $response;
    }
    
    /**
     * close 
     *
     *
    **/
    function close() {   
        curl_close($this->request);
        return TRUE;
    }
    
    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
    **/
    protected function set_request_headers() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->request, CURLOPT_HTTPHEADER, array('Expect:'));
    }
    
    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
    **/
    protected function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }
    
    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
    **/
    protected function set_request_options($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);
        curl_setopt($this->request, CURLOPT_CONNECTTIMEOUT, $this->_outtime);
        curl_setopt($this->request,CURLOPT_TIMEOUT,$this->_outtime);
        if (!empty($vars)) curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        
        //if (!empty($this->request_cookie)) curl_setopt($this->request, CURLOPT_COOKIE, $this->request_cookie);
       
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, 0);	//若没有使用证书，手动关闭(防止https自寻证书) 可注释
        
        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, true);
        //curl_setopt($this->request, CURLOPT_VERBOSE, true); //打印curl执行信息 可注释
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);

        if ($this->cookie_file) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
        }

        if ($this->follow_redirects) curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);
        
        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }
    
    /**
     * 创建文件夹
    **/
	public function mkdir_r($dirName, $rights=0777){
        $dirs = explode('/', trim($dirName, '/'));
        $dir = '';
        foreach ($dirs as $part) {
            $dir .= '/' . $part;
            if (!file_exists($dir))
                mkdir($dir, $rights);
        }
    }

}


/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
**/
class CurlResponse {
    
    /**
     * The body of the response without the headers block
     *
     * @var string
    **/
    public $body = '';
    
    /**
     * An associative array containing the response's headers
     *
     * @var array
    **/
    public $headers = array();
    
    /**
     * Accepts the result of a curl request as a string
     *
     * <code>
     * $response = new CurlResponse(curl_exec($curl_handle));
     * echo $response->body;
     * echo $response->headers['Status'];
     * </code>
     *
     * @param string $response
    **/
    function __construct($response) {
        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';
        
        # Extract headers from response
        preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));
        
        # Remove headers from the response body
        $this->body = str_replace($headers_string, '', $response);
        
        # Extract the version and status from the first header
        $version_and_status = array_shift($headers);
        if (preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches)) {
            $this->headers['Http-Version'] = $matches[1];
            $this->headers['Status-Code'] = $matches[2];
            $this->headers['Status'] = $matches[2].' '.$matches[3];
        }
        # Convert headers into an associative array
        $this->headers['Set-Cookie'] = '';
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            if ($matches[1] == 'Set-Cookie') {             
                $this->headers['Set-Cookie'] .= !empty($this->headers['Set-Cookie']) ? ";".$matches[2] : $matches[2];
            } else {
                $this->headers[$matches[1]] = $matches[2];
            }
        }
    }
    
    /**
     * Returns the response body
     *
     * <code>
     * $curl = new Curl;
     * $response = $curl->get('google.com');
     * echo $response;  # => echo $response->body;
     * </code>
     *
     * @return string
    **/
    function __toString() {
        return $this->body;
    }
    
    
}
