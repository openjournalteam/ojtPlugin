<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Exception;
use OjtPlugin;

class SubscriptionService
{
  public const SINGLE_JOURNAL = 'single_journal';
  public const INSTITUTIONAL = 'institutional';

  public $mode;

  protected $plugin;
  protected $baseUrl;
  protected $request;

  public function __construct($plugin, $mode = null)
  {
    $this->plugin = $plugin;
    $this->mode = $mode ?? static::SINGLE_JOURNAL;
  }

  public function getModeLabel($mode = null)
  {
    $mode = $mode ?? $this->mode;

    switch ($mode) {
      case static::SINGLE_JOURNAL:
        return 'Single Journal';
        break;
      case static::INSTITUTIONAL:
        return 'Institutional';
        break;
    }

    throw new Exception('Unknown subscription mode');
  }

  public function getSubscriptionApi($method = '')
  {
    return OjtPlugin::SERVICE_API . 'api/v2/subscription/' . $method;
  }

  public static function init($plugin, $mode = null)
  {
    return new static($plugin, $mode);
  }

  public function register($token)
  {
    try {
      $response = $this->apiRequest(
        'register',
        ['token' => $token]
      );

      if ($response['error']) {
        throw new Exception($response['message']);
      }

      $this->updateSetting(
        'quota',
        $response['quota']
      );

      $this->updateSetting(
        'registered',
        1
      );

      return $response;
    } catch (\Throwable $th) {
      throw $th;
    }

    return $response;
  }

  public function updateSetting($key, $value)
  {
    switch ($this->mode) {
      case static::SINGLE_JOURNAL:
        $contextId = $this->plugin->getCurrentContextId();
        break;
      case static::INSTITUTIONAL:
        $contextId = CONTEXT_SITE;
        break;
      default:
        throw new Exception('Unknown subscription mode');
        break;
    }

    return $this->plugin->updateSetting(
      $contextId,
      $key,
      $value
    );
  }

  public function getSetting($key, $default = null)
  {
    switch ($this->mode) {
      case static::SINGLE_JOURNAL:
        $contextId = $this->plugin->getCurrentContextId();
        break;
      case static::INSTITUTIONAL:
        $contextId = CONTEXT_SITE;
        break;
      default:
        throw new \Exception('Unknown subscription mode');
        break;
    }

    return $this->plugin->getSetting($contextId, $key) ?? $default;
  }

  public function quota()
  {
    try {
      $response = $this->apiRequest(
        'quota'
      );
      if ($response['error']) {
        throw new Exception($response['message']);
      }

      return $response;
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  public function refreshQuota()
  {
    $response = $this->quota();
    $this->updateSetting('quota', $response['quota']);
  }

  public function getQuota($refreshQuota = false)
  {
    if ($refreshQuota) {
      $this->refreshQuota();
    }

    return $this->getSetting('quota');
  }

  public function checkRegistered()
  {
    $response = $this->apiRequest(
      'check'
    );


    if ($response['error']) {
      throw new Exception($response['message']);
    }

    return $response;
  }

  public function isRegistered()
  {
    if ($this->getSetting('registered')) {
      return true;
    }

    $response = $this->checkRegistered();
    $isRegisteredOnServer = $response['exists'];
    if ($isRegisteredOnServer) {
      $this->updateSetting('registered', $isRegisteredOnServer);
      $this->updateSetting('quota', $response['quota']);
    }

    return $isRegisteredOnServer;
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

      $url = $this->getSubscriptionApi($method);

      $response = $client->post($url, [
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
    $headers = [
      'product_name' => $this->plugin->getName(),
      'subscription_mode' => $this->mode,
    ];

    if (method_exists($this->plugin, 'getPluginVersion')) {
      $headers['product_version'] = $this->plugin->getPluginVersion();
    }

    return $this->getOjtPlugin()->getHttpClient($headers);
  }

  protected function &getRequest()
  {
    if (!$this->request) {
      $this->request = &\Registry::get('request');
    }
    return $this->request;
  }

  /**
   * Ada 2 mode yang akan disediakan untuk system subscription ini.
   * Disaat SINGLE_JOURNAL mode aktif, maka kuota akan terscope hanya ke single journal tersebut.
   * Disaat INSTITUTIONAL aktif, maka kuota akan bisa digunakan oleh semua journal yang ada didalam 1 OJS. 
   * 
   * Semisal mode SINGLE_JURNAL maka base url yg akan digenerate adalah http://ojs.test/index.php/jcb
   * Semisal mode INSTITUTIONAL maka base url yg akan digenerate adalah http://ojs.test/index.php
   */
  public function getBaseUrl()
  {
    switch ($this->mode) {
      case static::SINGLE_JOURNAL:
        return $this->getRequest()->getDispatcher()->url($this->getRequest(), ROUTE_PAGE, $this->getRequest()->getContext()->getPath());
        break;
      case static::INSTITUTIONAL:
        return $this->getRequest()->getBaseUrl();
      default:
        throw new Exception('Unknown subscription mode');
        break;
    }
  }

  protected function getRequiredPayload()
  {
    $plugin = $this->getPlugin();
    return [
      'product' => $plugin->getName(),
      'journal_url' => $this->getBaseUrl(),
      'mode' => $this->mode
    ];
  }
}
