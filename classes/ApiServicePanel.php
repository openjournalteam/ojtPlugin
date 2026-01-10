<?php

namespace APP\plugins\generic\ojtControlPanel\classes;

use APP\plugins\generic\ojtControlPanel\OjtControlPanelPlugin;

class ApiServicePanel
{
    protected $plugin;

    protected $apiUrl = 'https://sp.openjournaltheme.com/api/v1/';

    // Staging Service Panel
    // protected $apiUrl = 'https://sp-staging.ojthost.xyz/api/v1/';

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

        $httpClient = OjtControlPanelPlugin::get()->getHttpClient();
        $httpHeaders = [
            'Accept' => 'application/json',
        ] + $headers;

        try {
            $response = $httpClient->post($url, [
                'headers' => $httpHeaders,
                'json' => $params
            ]);
        } catch (\Throwable $th) {
            error_log("Register Client Error:" . $th->getMessage());
            throw $th;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}