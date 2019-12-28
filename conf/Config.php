<?php


namespace conf;


class Config
{
    const ALLOWED_SERVER_TYPE = ['producer', 'remote'];

    // 生产者服务端配置
    const PRODUCER_HOST = '0.0.0.0';
    const PRODUCER_PORT = 9502;

    // 消费者客户端配置
    const CONSUMER_HOST = '127.0.0.1';
    const TIMEOUT = 1;

    // 远程服务端配置
    const REMOTE_HOST = '0.0.0.0';
    const REMOTE_PORT = 9501;

    // 远程客户端配置
    const REMOTE_CLIENT_HOST = '127.0.0.1';
    const REMOTE_CLIENT_TIMEOUT = 1;

    // redis服务端配置
    const REDIS_HOST = '127.0.0.1';
    const REDIS_PORT = '6379';
    const REDIS_AUTH = '';

    // 邮件配置
    const SMTP_SERVER = 'smtp.163.com';
    const SMTP_USERNAME = 'huangxu4328@163.com';
    const SMTP_PWD = 'Aa123456';
    const SMTP_FROM = 'huangxu4328@163.com';
    // 允许接收邮件的邮箱地址
    const ALLOWED_EMAIL_ADDRESS = [
        '234769003@qq.com'
    ];

    // hash_ids的相关配置
    const SALT = '123456';
    const MIN_HASH_LENGTH = 32;
}