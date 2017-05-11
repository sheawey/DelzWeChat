<?php

namespace Delz\WeChat\Model;

/**
 * jsApiTicket模型类
 *
 * @package Delz\WeChat\Model
 */
class JsApiTicket implements \Serializable
{
    /**
     * 微信JS接口的临时票据
     *
     * @var string
     */
    private $ticket;

    /**
     * 临时票据过期时间
     *
     * @var \DateTime
     */
    private $expiredAt;

    /**
     * 构造函数
     *
     * @param string $ticket 微信JS接口的临时票据
     * @param int $expiresIn 有效时间
     */
    public function __construct($ticket, $expiresIn)
    {
        $this->ticket = $ticket;
        //将临时票据减去100秒，以便于在临时票据失效之前去更新
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
     * 获取微信JS接口的临时票据
     *
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * 获取临时票据过期时间
     *
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
            'ticket' => $this->ticket,
            'expired_at' => $this->expiredAt
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->ticket = $data['ticket'];
        $this->expiredAt = $data['expired_at'];
    }

}