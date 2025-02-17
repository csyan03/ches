<?php
/**
 * 腾讯视频VIP一次解析api源码
 * Version 1.0
 *
 * Copyright 2023, 苏晓晴
 * Released under the MIT license
 */

//使用教程 
//参数 url=视频地址 type=输出方式(json/dplayer)
error_reporting(0);
// allow cross
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$url = isset($_GET['url']) ? $_GET['url'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$api = new Tvideo();

$data = $api->Tvideo_api($url);

if ($type == 'dplayer') {
    $file_path = __DIR__ . '/src/DPlayer.html';
    if (file_exists($file_path)) {
        echo file_get_contents($file_path);
    }
    exit;
}else{
    header('content-type:application/json;charset=utf8');
    exit(json_encode($data,480));   
}
//腾讯视频解析
class Tvideo
{
    public function Tvideo_api($url) {
        $content = $this->curl($url);
        preg_match('#data-vid="(\w+)" data-cid="(\w+)"#iU',$content,$id);
        $cid = !isset($id[2])?:$id[2];
        if (basename($url,'.html')==$cid) {
            $vid = !isset($id[1])?:$id[1];
        }else {
            $vid=basename($url,'.html');
        }
        $curl ='https://vv.video.qq.com/getinfo?encver=2&defn=shd&platform=10801&otype=ojson&sdtfrom=v4138&appVer=7&dtype=3&vid='.$vid.'&newnettype=1';
        
        $VIPCookies = '';// 必填VIP Cookies
        $JsonInfo = $this->vip_curl($curl,$VIPCookies);
        $JsonData = json_decode($JsonInfo,true);
        $vurl = $JsonData["vl"]["vi"][0]['ul']['ui'][3]['url'].$JsonData["vl"]["vi"][0]['ul']['ui'][3]['hls']['pt'];
        
    	if($JsonData['em']==61){
    	    $result = array(
    	      'code' => 1,
    	      'msg' => '解析失败！'
    	    );    
    	}else{
    	    $result = array(
              'code' => 0,
              'msg' => '解析成功！',
              'url' => $vurl,
              'm3u8_to'=>$JsonData["vl"]["vi"][0]['ul']['ui'][3]['url']
            );    
        }
        return $result;
    }

    public function curl($url) {
		$curl = curl_init();
		$header = array( 
		    	    "X-FORWARDED-FOR:".long2ip(mt_rand(1884815360, 1884890111)),
		    		"CLIENT-IP:".long2ip(mt_rand(1884815360, 1884890111)),
		    		"X-Real-IP:".long2ip(mt_rand(1884815360, 1884890111)),
		    		"Accept:application/json, text/javascript, */*; q=0.01",
		    		"Accept-Language:zh-CN,zh;q=0.9",
		    		"Connection: Keep-Alive",
		    		"Referer:https://v.qq.com",
		    		"User-Agent:Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36 QIHU 360EE",
		    		"X-Requested-With:XMLHttpRequest",
		         );
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($curl, CURLOPT_HEADER,0);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		return curl_exec($curl);
	}

    public function getC($str, $leftStr, $rightStr)
    {
        $left = strpos($str, $leftStr);
        $right = strpos($str, $rightStr,$left);
        if($left < 0 or $right < $left) return '';
        return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
    }
    
    public function vip_curl($url,$cookie='')
    {
        $header = array (
            0 => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            1 => 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,zh-HK;q=0.7',
            2 => 'Cache-Control: max-age=0',
            3 => 'Connection: keep-alive',
            4 => 'Sec-Fetch-Dest: document',
            5 => 'Sec-Fetch-Mode: navigate',
            6 => 'Sec-Fetch-Site: none',
            7 => 'Sec-Fetch-User: ?1',
            8 => 'Upgrade-Insecure-Requests: 1',
            9 => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            10 => 'sec-ch-ua: ^\\^',
            11 => 'sec-ch-ua-mobile: ?0',
            12 => 'sec-ch-ua-platform: ^\\^',
            13 =>'Cookie:'.$cookie
          );
        $timeout = 10;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if(substr($url, 0, 8) === 'https://') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if(!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, 1);              
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if(!empty($cookie)) {
            $header[] = $cookie;
        }
        if(!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
        $content = curl_exec($ch);
        if($error = curl_error($ch)) {
            error_log($error);
        }
        curl_close($ch);
        return $content;
    }
    
    public function rand_ip(){
        $ip_long = array(
        array('607649792', '608174079'), //36.56.0.0-36.63.255.255
        array('975044608', '977272831'), //58.30.0.0-58.63.255.255
        array('999751680', '999784447'), //59.151.0.0-59.151.127.255
        array('1019346944', '1019478015'), //60.194.0.0-60.195.255.255
        array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
        array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
        array('1947009024', '1947074559'), //116.13.0.0-116.13.255.255
        array('1987051520', '1988034559'), //118.112.0.0-118.126.255.255
        array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
        array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
        array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
        array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
        array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
        array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
        array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 14);
        $huoduan_ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        return $huoduan_ip;
    }
}