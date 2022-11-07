<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Exception;

class SubscriptionService
{
  const URL = "https://openjournaltheme.com/api/v2/subscription/";

  protected $plugin;
  protected $baseUrl;
  protected $request;

  public function __construct($plugin)
  {
    $this->plugin = $plugin;
  }

  public static function init($plugin)
  {
    return new static($plugin);
  }


  public function register($token)
  {
    try {
      $plugin = $this->getPlugin();

      $response = $this->apiRequest(
        'register',
        ['token' => $token]
      );

      if ($response['error']) {
        throw new Exception($response['message']);
      }

      $plugin->updateSetting(
        $plugin->getCurrentContextId(),
        'quota',
        $response['quota']
      );

      $plugin->updateSetting(
        $plugin->getCurrentContextId(),
        'quota_left',
        $response['quota']
      );
    } catch (\Throwable $th) {
      throw $th;
    }

    return $response;
  }

  /**
   * TODO add api to check quota
   */
  public function getQuota()
  {
    try {
      $response = $this->apiRequest(
        'quota',
      );

      if ($response['error']) {
        throw new Exception($response['message']);
      }

      return $response;
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  protected function getPlugin()
  {
    return $this->plugin;
  }

  protected function getOjtPlugin()
  {
    return \OjtPlugin::get();
  }

  protected function apiRequest($method, $payload = [], $asJson = true)
  {
    try {
      $client = $this->getClient();

      $response = $client->post(static::URL . $method, [
        'form_params' => array_merge($this->getRequiredPayload(), $payload),
      ]);

      if ($asJson) {
        return json_decode((string) $response->getBody(), true);
      }

      return json_decode((string) $response->getBody(), false);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      return json_decode((string) $e->getResponse()->getBody(), true);
    } catch (\Throwable $e) {
      throw $e;
    }
  }

  public function getClient()
  {
    return $this->getOjtPlugin()->getHttpClient();
  }

  protected function &getRequest()
  {
    if (!$this->request) {
      $this->request = &\Registry::get('request');
    }
    return $this->request;
  }

  protected function getBaseUrl()
  {
    if (!$this->baseUrl) {
      $this->baseUrl = $this->getRequest()->getDispatcher()->url($this->getRequest(), ROUTE_PAGE, $this->getRequest()->getContext());
    }
    return $this->baseUrl;
  }

  protected function getRequiredPayload()
  {
    $plugin = $this->getPlugin();
    return [
      'product' => $plugin->getName(),
      'journal_url' => $this->getBaseUrl(),
    ];
  }
}
