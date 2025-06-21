<?php

namespace Openjournalteam\OjtPlugin\Classes;

class ApiServicePanel
{
    protected $plugin;

    protected $apiUrl = 'http://127.0.0.1:8000/api/v1/';

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public static function make(...$args)
    {
        return new static(...$args);
    }

    public function getApiUrl($path = '')
    {
        return $this->apiUrl . $path;
    }

    public function registerClient($params = [], $headers = [])
    {
        $url = $this->getApiUrl('product/register-client');

        $httpClient = \OjtPlugin::get()->getHttpClient();
        $httpHeaders = [
            'Accept' => 'application/json',
        ] + $headers;

        try {
            $response = $httpClient->post($url, [
                'headers' => $httpHeaders,
                'json' => $params
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}