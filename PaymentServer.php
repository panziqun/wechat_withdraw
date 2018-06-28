<?php
/**
 * Created by pzq.
 * User: Administrator
 * Date: 2018-06-22
 * Time: 16:46
 */

namespace app\store\model\logic\server;

use think\facade\Config;

class StorePaymentServer
{
    static $mchid = '';
    static $certpem = '';
    static $keypem = '';
    static $key = '';
    //卖家提现到零钱
    public static function payToStoreCoin($partner_trade_no, $openid, $amount)
    {
        //申请商户号的appid或商户号绑定的appid   wx8888888888888888
        $array['mch_appid'] = '';
        //微信支付分配的商户号        	1900000109
        $array['mchid'] = self::$mchid;
        //	随机字符串，不长于32位        	5K8264ILTKCH16CQ2502SI8ZNMTM67VS
        $array['nonce_str'] = self::create_nonce_str();
        //商户订单号，需保持唯一性
        //(只能是字母或者数字，不能包含有符号)       10000098201411111234567890
        $array['partner_trade_no'] = $partner_trade_no;
        //商户appid下，某用户的openid
        $array['openid'] = $openid;
        //NO_CHECK：不校验真实姓名
        //FORCE_CHECK：强校验真实姓名
        $array['check_name'] = 'NO_CHECK';
        //企业付款金额，单位为分       10099
        $array['amount'] = $amount;
        //企业付款操作说明信息  理赔
        $array['desc'] = '提现';
        //该IP同在商户平台设置的IP白名单中的IP没有关联，该IP可传用户端或者服务端的IP    192.168.0.1
        $array['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];
        //	签名
        $array['sign'] = self::getSign($array);
        $xml = self::arrayToXml($array);
        return self::curlPost($xml);
    }
    //查询企业付款
    public static function getPayStatus($partner_trade_no)
    {
        $array['mch_id'] = self::$mchid;
        $array['appid'] = '';
        $array['partner_trade_no'] = $partner_trade_no;
        $array['nonce_str'] = self::create_nonce_str();
        $array['sign'] = self::getSign($array);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        return self::curlPost(self::arrayToXml($array), $url);
    }
    //请求微信企业付款接口
    public static function curlPost($postData, $url = '')
    {
        if (empty($url))
        {
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        }
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //证书
//        curl_setopt($curl,CURLOPT_SSLCERTTYPE,'PEM');
//        curl_setopt($curl,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($curl, CURLOPT_SSLCERT,dirname(__FILE__) . self::$certpem);
        curl_setopt($curl, CURLOPT_SSLKEY, dirname(__FILE__) . self::$keypem);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return self::xmlToArray($data);
    }
    //创建随机字符串
    public static function create_nonce_str()
    {
        $str = md5(mt_rand(9, 999));
        return str_shuffle($str);
    }
    //生成签名
    public static function getSign($params)
    {
        //排序
        ksort($params);
        $string = '';
        foreach ($params as $k => $v)
        {
            $string .= $k . '=' . $v . '&';
        }
        $string .= 'key=' . self::$key;
        return strtoupper(md5($string));
//        return strtoupper(hash_hmac('sha256', $string, $key));
    }
    //数组转XML
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            $xml.="<".$key.">".$val."</".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }
    //将XML转为array
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}
