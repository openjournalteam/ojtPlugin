<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Config;

class ApiServicePanel
{
    protected $plugin;

    protected $apiUrl;

    protected $productionApiUrl = 'https://sp-staging.ojthost.xyz/api/v1/';
    protected $localApiUrl = 'http://127.0.0.1:8001/api/v1/';

    // Staging Service Panel
    // protected $apiUrl = 'https://sp-staging.ojthost.xyz/api/v1/';

    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;

        $appEnv = Config::getVar('ojt_plugin', 'app_env') ?: (Config::getVar('ojt_control_panel', 'app_env') ?: Config::getVar('ojt_advance_security', 'app_env'));
        $this->apiUrl = ($appEnv === 'local') ? $this->localApiUrl : $this->productionApiUrl;
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
            error_log("Register Client Error:" . $th->getMessage());
            throw $th;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}