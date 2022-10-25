<?php

namespace Hsk99\PushClient;

class Client
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected static $_httpCLient = null;

    /**
     * @var \Monolog\Logger
     */
    protected static $_logger = null;

    /**
     * @var string
     */
    protected static $_logPath = null;

    /**
     * @var string
     */
    protected static $_serviceDomain = null;

    /**
     * @var string
     */
    protected static $_accessKey = null;

    /**
     * @var string
     */
    protected static $_secretKey = null;

    /**
     * @var float
     */
    protected static $_timeout = 3;

    /**
     * 私有频道鉴权
     *
     * @author HSK
     * @date 2022-08-22 16:15:41
     *
     * @param string $socket_id
     * @param string $channel_name
     * @param string|null $user_id
     * @param string|null $user_info
     *
     * @return array
     */
    public static function connectAuth(string $socket_id, string $channel_name, string $user_id = null, string $user_info = null): array
    {
        $path = '/api/connect/auth';
        $body = [
            'access_key'   => static::$_accessKey,
            'socket_id'    => $socket_id,
            'channel_name' => $channel_name,
            'user_id'      => $user_id,
            'user_info'    => $user_info,
        ];
        if (!isset($user_id)) {
            unset($body['user_id']);
            unset($body['user_info']);
        }

        return static::httpSend($path, $body);
    }

    /**
     * 订阅发布
     *
     * @author HSK
     * @date 2022-08-22 16:16:54
     *
     * @param string $channel
     * @param string $event
     * @param string $data
     * @param array $exclude_socket_id
     *
     * @return void
     */
    public static function channelPublish(string $channel, string $event, string $data = '{}', array $exclude_socket_id = []): array
    {
        $path = '/api/channel/publish';
        $body = [
            'channel'           => $channel,
            'event'             => $event,
            'data'              => $data,
            'exclude_socket_id' => $exclude_socket_id,
        ];
        if (empty($exclude_socket_id)) {
            unset($body['exclude_socket_id']);
        }

        return static::httpSend($path, $body);
    }

    /**
     * 在线订阅channel列表
     *
     * @author HSK
     * @date 2022-08-23 09:29:09
     *
     * @param string|null $type
     *
     * @return array
     */
    public static function channelList(string $type = null): array
    {
        $path = '/api/channel/list';
        $body = [
            'type' => $type,
        ];
        if (!isset($type)) {
            unset($body['type']);
        }

        return static::httpSend($path, $body);
    }

    /**
     * 在线订阅channel详情
     *
     * @author HSK
     * @date 2022-08-23 09:32:11
     *
     * @param string $channel
     *
     * @return array
     */
    public static function channelInfo(string $channel): array
    {
        $path = '/api/channel/info';
        $body = [
            'channel' => $channel,
        ];

        return static::httpSend($path, $body);
    }

    /**
     * 发送数据
     *
     * @author HSK
     * @date 2022-08-22 15:26:58
     *
     * @param string $path
     * @param array $body
     *
     * @return array
     */
    protected static function httpSend(string $path, array $body): array
    {
        if (is_null(static::$_httpCLient)) {
            static::$_httpCLient = new \GuzzleHttp\Client([
                'verify'  => false,
                'timeout' => static::$_timeout,
            ]);
        }

        static::log('request', ['path' => $path, 'body' => $body]);

        try {
            $signature = hash_hmac('sha256', json_encode($body, 320), static::$_secretKey, false);

            $response = static::$_httpCLient->request(
                'POST',
                static::$_serviceDomain . $path,
                [
                    'form_params' => $body,
                    'headers'     => [
                        'Content-Type'      => 'multipart/form-data',
                        'Accept'            => 'application/json',
                        'X-hsk99-Key'       => static::$_accessKey,
                        'X-hsk99-Signature' => $signature
                    ]
                ]
            );
        } catch (\Throwable $th) {
            static::log('exception', ['code' => $th->getCode(), 'msg' => $th->getMessage()]);
            return ['code' => $th->getCode(), 'msg' => $th->getMessage()];
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $body = (string)$response->getBody();
            static::log('exception', ['code' => $status, 'msg' => $body]);
            return ['code' => $status, 'msg' => $body];
        }

        $response_body = json_decode($response->getBody(), true);

        static::log('response', ['path' => $path, 'body' => $response_body]);
        return $response_body;
    }

    /**
     * 日志
     *
     * @author HSK
     * @date 2022-08-23 09:59:50
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected static function log(string $message, array $context = [])
    {
        if (is_null(static::$_logger)) {
            if (function_exists('runtime_path')) {
                $filename = runtime_path() . '/logs/push/push.log';
            } else if (!is_null(static::$_logPath) && is_dir(static::$_logPath)) {
                $filename = static::$_logPath . '/push.log';
            } else {
                $filename = __DIR__ . '/../../../../logs/push/push.log';
            }

            $handler = new \Monolog\Handler\RotatingFileHandler($filename, 7, \Monolog\Logger::DEBUG);
            $formatter = new \Monolog\Formatter\LineFormatter("[%datetime%] %message% %context% %extra%\n", 'Y-m-d H:i:s', true);
            $handler->setFormatter($formatter);
            static::$_logger = new \Monolog\Logger('push');
            static::$_logger->pushHandler($handler);
        }

        static::$_logger->debug($message, $context);
    }

    /**
     * 设置配置
     *
     * @author HSK
     * @date 2022-08-22 14:12:01
     *
     * @param array $config
     *
     * @return void
     */
    public static function setConfig(array $config = [])
    {
        static::$_serviceDomain = $config['service_domain'] ?? null;
        static::$_accessKey     = $config['access_key'] ?? null;
        static::$_secretKey     = $config['secret_key'] ?? null;
        static::$_logPath       = $config['log_path'] ?? null;
        static::$_timeout       = $config['timeout'] ?? 3;
    }

    /**
     * Webman 启动，自动加载配置
     * 
     * @author HSK
     * @date 2022-08-22 14:19:29
     *
     * @param \Workerman\Worker $worker
     *
     * @return void
     */
    public static function start($worker)
    {
        if ($worker instanceof \Workerman\Worker) {
            static::$_serviceDomain = config('plugin.hsk99.push-client.app.service_domain');
            static::$_accessKey     = config('plugin.hsk99.push-client.app.access_key');
            static::$_secretKey     = config('plugin.hsk99.push-client.app.secret_key');
            static::$_timeout       = config('plugin.hsk99.push-client.app.timeout', 3);
        }
    }
}
