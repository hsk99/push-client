
# push-client 订阅发布插件

## 简介

> 配合 [PushService](https://github.com/hsk99/push-service) 使用，用于发布订阅数据。


## 安装

` composer require hsk99/push-client `


## webman 配置

修改 ` config/plugin/hsk99/push-client/app.php ` 文件，设置相关参数


## 使用

```php
<?php

// 非 webman 运行，设置配置参数
\Hsk99\PushClient\Client::setConfig([
            'service_domain' => 'http://127.0.0.1:8789',              // 服务域名
            'access_key'     => 'ecc1dcdecd380a38cadc74cd9d0fb9bf',   // 访问密钥
            'secret_key'     => '8af1ea94d8f73d5cc9ba384b59298c41',   // 密钥
            'log_path'       => null,                                 // 日志目录
        ]);

// 私有频道鉴权
\Hsk99\PushClient\Client::connectAuth('socket_id', 'my-channel');

// 订阅发布
\Hsk99\PushClient\Client::channelPublish('my-channel', 'my-event', 'data');

// 在线订阅channel列表
\Hsk99\PushClient\Client::channelList();

// 在线订阅channel详情
\Hsk99\PushClient\Client::channelInfo('my-channel');
```