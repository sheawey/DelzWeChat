<?php

namespace Delz\WeChat\Model;

/**
 * 微信access_token模型
 *
 * 由于到存储到缓存，所以模型要支持序列化
 *
 * @package Delz\WeChat\Model
 */
class AccessToken implements \Serializable
{
    /**
     * 微信调用的凭证
     *
     * @var string
     */
    private $token;

    /**
     * 凭证过期时间
     *
     * @var \DateTime
     */
    private $expiredAt;

    /**
     * @param string $token 微信凭证
     * @param int $expiresIn 微信凭证有效时间，单位：秒
     */
    public function __construct($token, $expiresIn)
    {
        $this->token = $token;
        //将凭证有效时间减去100秒，以便于在凭证失效之前去更新
        $expiresIn = $expiresIn - 100;
        //获取失效时间，当前时间+$expiresIn
        $this->expiredAt = new \DateTime();
        $this->expiredAt->add(new \DateInterval('PT' . $expiresIn . 'S'));
    }

    /**
     * 是否有效，过期就是无效
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->expiredAt > new \DateTime();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return \DateTime
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            'access_token' => $this->token,
            'expired_at' => $this->expiredAt
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->token = $data['access_token'];
        $this->expiredAt = $data['expired_at'];
    }

}