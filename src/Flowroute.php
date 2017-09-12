<?php

namespace NotificationChannels\Flowroute;

use GuzzleHttp;

/**
 * Class Flowroute
 * @package NotificationChannels\Flowroute
 */
class Flowroute
{
    /** @var string */
    protected $access_key;

    /** @var string */
    protected $secret_key;

    /** @var string */
    protected $from;

    /** @var false | string */
    protected $send_to_override;

    /** @var string */
    public $webhook_url;

    /** @var \GuzzleHttp\Client */
    protected $c;

    /** @var */
    protected $lastMessageTime;

    /**
     * Create a new Flowroute RestAPIinstance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->access_key = $config['access_key'];
        $this->secret_key = $config['secret_key'];
        $this->from = $config['from_number'];
        $this->send_to_override = $config['send_to_override'];
        $this->webhook_url = $config['webhook_url'];

        $this->c = new GuzzleHttp\Client([
            'base_uri' => 'https://api.flowroute.com/v2.1/',
        ]);
    }

    /**
     * Number SMS is being sent from.
     *
     * @return string
     */
    public function from()
    {
        return $this->from;
    }

    /**
     * @param $data
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function sendSms($data)
    {
        if ($this->send_to_override) $data['to'] = $this->send_to_override;
        return $this->post('messages', $data);
    }

    /**
     * @param $url
     * @param $data
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function post($url, $data)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        return $this->request('POST', $url, ['body' => $data]);
    }

    /**
     * @param $url
     * @param $data
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function get($url, $data) {
        return $this->request('GET', $url);
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $options
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function request($method, $url, $options = [])
    {
        $options = array_merge([
            'auth' => [ $this->access_key, $this->secret_key ],
            'headers' => ['content-type' => 'application/vnd.api+json']
        ], $options);

        $now = microtime(true);

        if ($now - $this->lastMessageTime < 1) {
            $sleepFor = ($now - $this->lastMessageTime) * 1000;
            // \Log::debug("[FLOWROUTE] Sleeping for $sleepFor to prevent throttling");
            usleep($sleepFor);
        }

        $this->lastMessageTime = microtime(true);

        return $this->c->request($method, $url, $options);
    }
}
