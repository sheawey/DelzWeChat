<?php

namespace Delz\WeChat;

use Delz\Cache\Contract\ICache;
use Delz\Common\Util\Http;
use Delz\WeChat\Model\AccessToken;
use Delz\WeChat\Model\JsApiTicket;
use Delz\Common\Util\Url;
use Delz\Common\Util\Str;

/**
 * 微信服务的客户端类，封装了用户通过微信API对微信服务的各种操作
 *
 * @package Delz\WeChat
 */
class Client
{
    /**
     * 第三方用户唯一凭证
     *
     * @var string
     */
    protected $appId;

    /**
     * 第三方用户唯一凭证密钥
     *
     * @var string
     */
    protected $appSecret;

    /**
     * @var ICache
     */
    protected $cache;

    /**
     * 获取access token
     */
    const API_ACCESS_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token';

    /**
     * 微信服务器的IP地址列表
     */
    const API_IP_LIST = 'https://api.weixin.qq.com/cgi-bin/getcallbackip';

    /**
     * 获取jsApiTicket
     */
    const API_TICKET_GET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    /**
     * 新增临时素材
     */
    const API_MEDIA_UPLOAD = 'http://file.api.weixin.qq.com/cgi-bin/media/upload';

    /**
     * 获取临时素材
     */
    const API_MEDIA_GET = 'http://file.api.weixin.qq.com/cgi-bin/media/get';

    /**
     * 自定义菜单创建接口
     */
    const API_MENU_CREATE = 'https://api.weixin.qq.com/cgi-bin/menu/create';

    /**
     * 自定义菜单查询接口
     */
    const API_MENU_GET = 'https://api.weixin.qq.com/cgi-bin/menu/get';

    /**
     * 自定义菜单删除接口
     */
    const API_MENU_DELETE = 'https://api.weixin.qq.com/cgi-bin/menu/delete';

    /**
     * 创建个性化菜单
     */
    const API_MENU_ADD_CONDITIONAL = 'https://api.weixin.qq.com/cgi-bin/menu/addconditional';

    /**
     * 删除个性化菜单
     */
    const API_MENU_DELETE_CONDITIONAL = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional';

    /**
     * 测试个性化菜单匹配结果
     */
    const API_MENU_TRY_MATCH = 'https://api.weixin.qq.com/cgi-bin/menu/trymatch';

    /**
     * 短网址
     */
    const API_SHORT_URL = 'https://api.weixin.qq.com/cgi-bin/shorturl';

    /**
     * 创建标签
     * 一个公众号，最多可以创建100个标签。
     */
    const API_TAG_CREATE = 'https://api.weixin.qq.com/cgi-bin/tags/create';

    /**
     * 获取公众号已创建的标签
     */
    const API_TAG_LIST = 'https://api.weixin.qq.com/cgi-bin/tags/get';

    /**
     * 编辑标签
     */
    const API_TAG_UPDATE = 'https://api.weixin.qq.com/cgi-bin/tags/update';

    /**
     * 删除标签
     * 当某个标签下的粉丝超过10w时，后台不可直接删除标签。
     * 此时，开发者可以对该标签下的openid列表，先进行取消标签的操作，直到粉丝数不超过10w后，才可直接删除该标签。
     */
    const API_TAG_DELETE = 'https://api.weixin.qq.com/cgi-bin/tags/delete';

    /**
     * 获取标签下粉丝列表
     */
    const API_TAG_USERS = 'https://api.weixin.qq.com/cgi-bin/user/tag/get';

    /**
     * 批量为用户打标签
     * 支持公众号为用户打上最多三个标签。
     */
    const API_TAGGING_USER = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging';

    /**
     * 批量为用户取消标签
     */
    const API_UNTAGGING_USER = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging';

    /**
     * 获取用户身上的标签列表
     */
    const API_USER_TAGS = 'https://api.weixin.qq.com/cgi-bin/tags/getidlist';

    /**
     * 获取用户列表
     */
    const API_USER_LIST = 'https://api.weixin.qq.com/cgi-bin/user/get';

    /**
     * 获取用户基本信息（包括UnionID机制）
     */
    const API_USER_GET = 'https://api.weixin.qq.com/cgi-bin/user/info';

    /**
     * 批量获取用户基本信息
     */
    const API_USERS_GET = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';

    /**
     * 设置用户备注名
     */
    const API_USER_REMARK = 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark';

    /**
     * 获取公众号的黑名单列表
     */
    const API_BLACK_LIST = 'https://api.weixin.qq.com/cgi-bin/tags/members/getblacklist';

    /**
     * 拉黑用户
     */
    const API_BATCH_BLACK = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchblacklist';

    /**
     * 取消拉黑用户
     */
    const API_BATCH_UNBLACK = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchunblacklist';

    /**
     * access_token缓存名称前缀
     */
    const ACCESS_TOKEN_CACHE_PREFIX = 'WeChatAccessToken_';

    /**
     * jsApiTicket的缓存名称前缀
     */
    const JS_API_TICKET_CACHE_PREFIX = 'WeChatJsSdk_';

    /**
     * 构造函数
     *
     * @param string $appId 第三方用户唯一凭证
     * @param string $appSecret 第三方用户唯一凭证密钥
     * @param ICache $cache 缓存对象, 用于缓存Access Token
     * @throws WeChatException
     */
    public function __construct($appId, $appSecret, ICache $cache)
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
        $this->cache = $cache;
    }

    /**
     * 获取access_token
     *
     * 1. 先从缓存中读取，如果access_token存在并且不过期，直接返回access_token
     * 2. 如果缓存中不存在或者已过期，重新调用微信接口获取，并将它存入缓存
     *
     * @return string
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183&token=&lang=zh_CN
     */
    public function getAccessToken()
    {
        //从缓存去读取
        $cacheKey = $this->getAccessTokenCacheKey($this->appId);
        /** @var AccessToken $accessToken */
        $accessToken = $this->cache->get($cacheKey);
        if ($accessToken && $accessToken->isAvailable()) {
            return $accessToken->getToken();
        }

        //直接从微信服务器拉取
        $params = [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret
        ];

        $response = Http::get(self::API_ACCESS_TOKEN, ['query' => $params]);
        $responseArr = json_decode($response->getBody(), true);

        if (isset($responseArr['errcode'])) {
            throw new WeChatException($responseArr['errmsg'], $responseArr['errcode']);
        }

        $accessToken = new AccessToken($responseArr['access_token'], $responseArr['expires_in']);

        //缓存永久存储，lifetime设为0
        $this->cache->set($cacheKey, $accessToken, $responseArr['expires_in']);

        return $accessToken->getToken();
    }

    /**
     * 获取微信服务器的IP地址列表
     *
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140187&token=&lang=zh_CN
     */
    public function getIpList()
    {
        $result = $this->post(self::API_IP_LIST);

        return $result['ip_list'];
    }

    /**
     * 获取微信JS接口的临时票据
     *
     * 1.先从缓存中读取，如果jsApiTicket存在并且不过期，直接返回jsApiTicket
     * 2.如果缓存中不存在或者已过期，重新调用微信接口获取，并将它存入缓存
     *
     * @return string
     */
    public function getJsApiTicket()
    {
        //从缓存中读取
        $cacheKey = $this->getJsApiTicketCacheKey($this->appId);
        /** @var JsApiTicket $ticket */
        $ticket = $this->cache->get($cacheKey);
        if ($ticket && $ticket->isAvailable()) {
            return $ticket->getTicket();
        }

        //直接从微信接口读取
        $params = [
            'type' => 'jsapi'
        ];
        $result = $this->post(self::API_TICKET_GET, $params);

        $ticket = new JsApiTicket($result['ticket'], $result['expires_in']);
        $this->cache->set($cacheKey, $ticket, $result['expires_in']);

        return $ticket->getTicket();
    }

    /**
     * 获取JsApiTicket配置参数
     *
     * @param array $APIs 需要使用的JS接口列表
     * @param bool|true $json 是否json输出
     * @param bool|false $debug 开启调试模式,调用的所有api的返回值会在客户端alert出来
     * @param null $url 不包含#及其后面部分,默认是当前脚本执行的url
     * @return array|string
     * @throws WeChatException
     */
    public function getJsApiTicketConfig(array $APIs, $json = true, $debug = false, $url = null)
    {
        $nonceStr = Str::random(10);
        $timestamp = time();
        $ticket = $this->getJsApiTicket();
        $url = $url ? $url : Url::current();
        $string = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($string);
        $data = [
            "debug" => $debug,
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
            "jsApiList" => $APIs
        ];
        return $json ? json_encode($data) : $data;
    }

    /**
     * 新增临时素材
     *
     * @param string $type 媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * @param string $mediaPath 媒体文件本地路径
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738726&token=&lang=zh_CN
     */
    public function uploadMedia($type, $mediaPath)
    {
        $url = self::API_MEDIA_UPLOAD . '?access_token=' . $this->getAccessToken() . '&type=' . $type;
        $params = [
            'media' => $mediaPath
        ];

        return $this->postFile($url, $params);
    }

    /**
     * 获取临时素材
     *
     * @param string $mediaId
     * @return string
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738727&token=&lang=zh_CN
     */
    public function getMedia($mediaId)
    {
        $params = [
            'media_id' => $mediaId
        ];

        return base64_encode($this->get(self::API_MEDIA_GET, $params));
    }

    /**
     * 自定义菜单创建接口
     *
     * @param array $buttons
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013&token=&lang=zh_CN
     */
    public function createMenu(array $buttons)
    {
        $this->postJson(self::API_MENU_CREATE, ['button' => $buttons]);

        return true;
    }

    /**
     * 自定义菜单查询接口
     *
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141014&token=&lang=zh_CN
     */
    public function getMenu()
    {
        return $this->get(self::API_MENU_GET);
    }

    /**
     * 自定义菜单删除接口
     *
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141015&token=&lang=zh_CN
     */
    public function deleteMenu()
    {
        $this->get(self::API_MENU_DELETE);

        return true;
    }

    /**
     * 创建个性化菜单
     *
     * 必须创建自定义菜单后才能创建个性化菜单，否则会抛出异常
     *
     * @param array $buttons
     * @param array $matchRule
     * @return string 新增菜单Id：menuid
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455782296&token=&lang=zh_CN
     */
    public function addConditionalMenu(array $buttons, array $matchRule)
    {
        $params = [
            'button' => $buttons,
            'matchrule' => $matchRule
        ];
        $result = $this->postJson(self::API_MENU_ADD_CONDITIONAL, $params);

        return $result['menuid'];
    }

    /**
     * 删除个性化菜单
     *
     * @param string $menuId
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455782296&token=&lang=zh_CN
     */
    public function deleteConditionalMenu($menuId)
    {
        $params = [
            'menuid' => $menuId
        ];

        $this->postJson(self::API_MENU_DELETE_CONDITIONAL, $params);

        return true;
    }

    /**
     * 测试个性化菜单匹配结果
     *
     * @param string $userId 可以是粉丝的OpenID，也可以是粉丝的微信号。
     * @return array
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455782296&token=&lang=zh_CN
     */
    public function menuTryMatch($userId)
    {
        $params = [
            'user_id' => $userId
        ];

        return $this->postJson(self::API_MENU_TRY_MATCH, $params);
    }

    /**
     * 将一条长链接转成短链接。
     *
     * @param string $longUrl 长网址
     * @return string 短网址
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1443433600&token=&lang=zh_CN
     */
    public function shortUrl($longUrl)
    {
        $params = [
            'action' => 'long2short',
            'long_url' => $longUrl,
        ];

        $result = $this->postJson(self::API_SHORT_URL, $params);

        return $result['short_url'];
    }

    /**
     * 创建用户标签
     *
     * @param string $name 标签名称
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function createTag($name)
    {
        $params = [
            'tag' => ['name' => $name],
        ];

        return $this->postJson(self::API_TAG_CREATE, $params);
    }

    /**
     * 获取公众号已创建的标签
     *
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function listTags()
    {
        $result = $this->get(self::API_TAG_LIST);

        return $result['tags'];
    }

    /**
     * 编辑用户标签
     *
     * @param int $tagId
     * @param string $name
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function updateTag($tagId, $name)
    {
        $params = [
            'tag' => [
                'id' => $tagId,
                'name' => $name,
            ],
        ];

        $this->postJson(self::API_TAG_UPDATE, $params);

        return true;
    }

    /**
     * 删除标签
     *
     * @param int $tagId
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function deleteTag($tagId)
    {
        $params = [
            'tag' => [
                'id' => $tagId,
            ],
        ];

        $this->postJson(self::API_TAG_DELETE, $params);

        return true;
    }

    /**
     * 获取标签下粉丝列表
     *
     * @param int $tagId
     * @param null|string $nextOpenId
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function getTagUsers($tagId, $nextOpenId = null)
    {
        $params = [
            'tagid' => $tagId,
            'next_openid' => $nextOpenId
        ];

        return $this->postJson(self::API_TAG_USERS, $params);
    }

    /**
     * 批量为用户打标签
     *
     * @param array $openIds
     * @param int $tagId
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function addTagToUser(array $openIds, $tagId)
    {
        $params = [
            'openid_list' => $openIds,
            'tagid' => $tagId,
        ];

        $this->postJson(self::API_TAGGING_USER, $params);

        return true;
    }

    /**
     * 批量为用户取消标签
     *
     * @param array $openIds
     * @param int $tagId
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837&token=&lang=zh_CN
     */
    public function removeTagToUser(array $openIds, $tagId)
    {
        $params = [
            'openid_list' => $openIds,
            'tagid' => $tagId,
        ];

        $this->postJson(self::API_UNTAGGING_USER, $params);

        return true;
    }

    /**
     * 获取用户身上的标签列表
     *
     * @param string $openId
     * @return array
     * @throws WeChatException
     */
    public function getUserTags($openId)
    {
        $params = [
            'openid' => $openId
        ];

        $result = $this->postJson(self::API_USER_TAGS, $params);

        return $result['tagid_list'];

    }

    /**
     * 获取所有用户列表
     *
     * @param null|string $nextOpenId 第一个拉取的OPENID，不填默认从头开始拉取
     * @return array    total 关注该公众账号的总用户数
     *                  count 拉取的OPENID个数，最大值为10000
     *                  data 列表数据，OPENID的列表
     *                  next_openid 拉取列表的最后一个用户的OPENID
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140840&token=&lang=zh_CN
     */
    public function allUsers($nextOpenId = null)
    {
        $params = [
            'next_openid' => $nextOpenId
        ];

        return $this->get(self::API_USER_LIST, $params);
    }

    /**
     * 获取用户基本信息（包括UnionID机制）
     *
     * @param string $openId 普通用户的标识，对当前公众号唯一
     * @param string $lang 返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839&token=&lang=zh_CN
     */
    public function getUser($openId, $lang = 'zh_CN')
    {
        $params = [
            'openid' => $openId,
            'lang' => $lang,
        ];

        return $this->get(self::API_USER_GET, $params);
    }

    /**
     * 批量获取用户基本信息(根据openId)
     *
     * @param array $openIds
     * @param string $lang
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839&token=&lang=zh_CN
     */
    public function getUsers(array $openIds, $lang = 'zh_CN')
    {
        $params = [];
        $params['user_list'] = array_map(function ($openId) use ($lang) {
            return [
                'openid' => $openId,
                'lang' => $lang,
            ];
        }, $openIds);

        $result = $this->postJson(self::API_USERS_GET, $params);

        return $result['user_info_list'];
    }

    /**
     * 设置用户备注名
     *
     * @param string $openId
     * @param string $remark
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140838&token=&lang=zh_CN
     */
    public function remarkUser($openId, $remark)
    {
        $params = [
            'openid' => $openId,
            'remark' => $remark,
        ];

        $this->postJson(self::API_USER_REMARK, $params);

        return true;
    }

    /**
     * 获取公众号的黑名单列表
     *
     * @param null|string $beginOpenId
     * @return array
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1471422259_pJMWA&token=&lang=zh_CN
     */
    public function blackList($beginOpenId = null)
    {
        $params = [
            'begin_openid' => $beginOpenId,
        ];

        return $this->postJson(self::API_BLACK_LIST, $params);
    }


    /**
     * 拉黑用户
     * //todo 测试不成功
     *
     * @param array $openIdList
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1471422259_pJMWA&token=&lang=zh_CN
     */
    public function batchBlack(array $openIdList = [])
    {
        $params = [
            'opened_list' => $openIdList,
        ];

        $this->postJson(self::API_BATCH_BLACK, $params);

        return true;
    }

    /**
     * 取消拉黑用户
     * //todo 测试不成功
     *
     * @param array $openIdList
     * @return bool
     * @throws WeChatException
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1471422259_pJMWA&token=&lang=zh_CN
     */
    public function batchCancelBlack(array $openIdList = [])
    {
        $params = [
            'opened_list' => $openIdList,
        ];

        $this->postJson(self::API_BATCH_UNBLACK, $params);

        return true;
    }


    /**
     * 重置access_token
     *
     * 主要考虑到有两个不同的服务请求了access_token，一个请求会把另一个保存的缓存中的accessToken失效掉，
     * 微信建议是如果有不同的服务请求AccessToken，由中控服务器控制access_token
     *
     * 所以每次返回接口错误码是40001的时候，可以重置AccessToken，并重新请求接口
     */
    private function resetAccessToken()
    {
        $cacheKey = $this->getAccessTokenCacheKey($this->appId);
        $this->cache->delete($cacheKey);
    }

    /**
     * 获取access token保存在缓存中的键值
     *
     * @param string $appId
     * @return string
     */
    private function getAccessTokenCacheKey($appId)
    {
        return self::ACCESS_TOKEN_CACHE_PREFIX . $appId;
    }

    /**
     * 获取jsApiTicket保存在缓存中的键值
     *
     * @param string $appId
     * @return string
     */
    private function getJsApiTicketCacheKey($appId)
    {
        return self::JS_API_TICKET_CACHE_PREFIX . $appId;
    }

    /**
     *
     * 检测一个字符串否为Json字符串（只针对微信部分，不具有通用性）
     *
     * 如果是json数据，将decode后的数据保存到$returnData
     *
     * $returnData是引用传递，目的是减少一次json_decode
     *
     * @param string $string 检查的字符串
     * @param mixed $returnData 返回的数据
     * @return true/false
     */
    private function isJson($string, &$returnData)
    {
        if (strpos($string, "{") !== false) {
            $returnData = json_decode($string, true);
            return (json_last_error() == JSON_ERROR_NONE);
        } else {
            return false;
        }
    }


    /**
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @param string $method 请求方法:get或post(目前微信接口只需要此两种)
     * @return mixed
     * @throws WeChatException
     */
    private function request($url, array $params = [], $method = 'get')
    {
        $method = strtolower($method);
        $params['access_token'] = $this->getAccessToken();
        if ($method == 'get') {
            $response = Http::get($url, ['query' => $params]);
        } elseif ($method == 'post') {
            $response = Http::post($url, ['form_params' => $params]);
        } else {
            throw new WeChatException(sprintf("method %s is not supported.", $method));
        }
        if ($this->isJson($response->getBody(), $result)) {
            //如果是access_token失效，重置access_token，并重新请求
            //微信请求access_token失效的状态码是40001
            if (isset($result['errcode']) && $result['errcode'] == 40001) {
                $this->resetAccessToken();
                unset($params['access_token']);
                return $this->request($url, $params, $method);
            }
            //检查返回结果，如果错误，抛出异常
            if (isset($result['errcode']) && $result['errcode'] !== 0) {
                throw new WeChatException($result['errmsg'], $result['errcode']);
            }
            return $result;
        } else {
            return $response->getBody();
        }
    }

    /**
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @return mixed
     */
    private function get($url, array $params = [])
    {
        return $this->request($url, $params, 'get');
    }

    /**
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @return mixed
     */
    private function post($url, array $params = [])
    {
        return $this->request($url, $params, 'post');
    }

    /**
     * 上传文件
     *
     * @param string $url 请求网址
     * @param array $params 请求参数，文件路径数组
     * @return mixed
     */
    private function postFile($url, array $params = [])
    {
        //php版本不同，方式不同，大于5.6用CURLFile,小于用@标记
        if (version_compare("5.6", PHP_VERSION, ">")) {
            //5.6及以上版本
            foreach ($params as $k => $v) {
                $params[$k] = '@' . $v;
            }
        } else {
            foreach ($params as $k => $v) {
                $params[$k] = new \CURLFile(realpath($v));
            }
        }
        return $this->post($url, $params);
    }

    /**
     * 传输内容主体是json格式的请求方式
     *
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @param string $method 请求方法:get或post(目前微信接口只需要此两种)
     * @return mixed
     * @throws WeChatException
     */
    private function requestJson($url, array $params = [], $method = 'get')
    {
        $url = $url . '?access_token=' . $this->getAccessToken();
        if ($method == 'get') {
            //一般没有get的
            //微信中文不能不转义中文汉字
            $response = Http::get($url, ['body' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
        } elseif ($method == 'post') {
            //微信中文不能不转义中文汉字
            $response = Http::post($url, ['body' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
        } else {
            throw new WeChatException(sprintf("method %s is not supported.", $method));
        }
        if ($this->isJson($response->getBody(), $result)) {
            //如果是access_token失效，重置access_token，并重新请求
            if (isset($result['errcode']) && $result['errcode'] == 40001) {
                $this->resetAccessToken();
                unset($params['access_token']);
                return $this->requestJson($url, $params, $method);
            }
            //检查返回结果，如果错误，抛出异常
            if (isset($result['errcode']) && $result['errcode'] !== 0) {
                throw new WeChatException($result['errmsg'], $result['errcode']);
            }
            return $result;
        } else {
            return $response->getBody();
        }
    }

    /**
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @return mixed
     */
    private function postJson($url, array $params = [])
    {
        return $this->requestJson($url, $params, 'post');
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }
}