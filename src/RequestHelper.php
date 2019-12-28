<?php


namespace src;


use Exception;

final class RequestHelper
{
    static public $sl_cert_path = '';
    static public $ssl_key_pem = '';

    /**
     * @param $sl_cert_path
     */
    static public function setSlCertPath($sl_cert_path)
    {
        self::$sl_cert_path = $sl_cert_path;
    }

    /**
     * @param $ssl_key_pem
     */
    static public function setSslKeyPem($ssl_key_pem)
    {
        self::$ssl_key_pem = $ssl_key_pem;
    }

    static public function curlRequest($url, $data = [], $method = 'GET', $headers = [], $use_cert = false, $second = 30, $return_resp_code = False)
    {
        $ch = curl_init();
        //设置超时与地址
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (strpos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
        }

        //设置header
        if(!empty($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        }else{
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($use_cert === true) {
            //设置证书
            if (empty(self::$sl_cert_path))
                throw new Exception('cert证书路径不能为空');
            else{
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLCERT, self::$sl_cert_path);
            }

            if (empty(self::$ssl_key_pem))
                throw new Exception('pem证书路径不能为空');
            else {
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLKEY, self::$ssl_key_pem);
            }
        }

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $document = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if ($return_resp_code === true) {
            $resp_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            return [$resp_code, $header_size, $document];
        }

        if(curl_errno($ch)){
            throw new Exception('接口调用失败');
        }
        curl_close($ch);

        return [$header_size, $document];
    }

}