<?php

namespace Delz\WeChat;

use Delz\Common\Util\Http;

/**
 * 小程序客户端类
 *
 * @package Delz\WeChat
 */
class MiniClient
{
    /**
     * 小程序appId
     *
     * @var string
     */
    private $appId;

    /**
     * 小程序app秘钥
     *
     * @var string
     */
    private $appSecret;

    /**
     * code 换取 session_key接口
     */
    const API_JS_CODE_TO_SESSION = 'https://api.weixin.qq.com/sns/jscode2session';

    /**
     * @param string $appId
     * @param string $appSecret
     * @throws WeChatException
     */
    public function __construct($appId, $appSecret)
    {
        $appId = trim($appId);
        $appSecret = trim($appSecret);

        if (empty($appId)) {
            throw new WeChatException('app id is empty');
        }

        if (empty($appSecret)) {
            throw new WeChatException('app secret is empty');
        }

        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }


    /**
     * 通过code 换取 session_key
     *
     * @param string $code 登录时获取的 code
     * @return array 返回openid和session_key
     * @throws WeChatException
     */
    public function getSessionKeyByCode($code)
    {
        $parameters = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $response = Http::get(self::API_JS_CODE_TO_SESSION, ['query' => $parameters]);
        $result = json_decode($response->getBody(),true);
        if(isset($result['errcode'])) {
            throw new WeChatException($result['errmsg'], $result['errcode']);
        }
        return $result;
    }
}