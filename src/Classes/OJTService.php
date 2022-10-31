<?php

namespace Openjournalteam\OjtPlugin\Classes;

class OJTService
{
    const URL = "https://sp.thisnugroho.my.id";

    // public $service;

    // public function __construct($service)
    // {
    //     $this->service = $service;
    // }

    public function request($method, $payload, $asJson = true)
    {
        try {
            $client = $this->requestInit();

            $response = $client->post(static::URL . $method, [
                'form_params' => $payload,
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

    protected function requestInit()
    {
        return new \GuzzleHttp\Client([
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                // 'x-openjournaltheme' => 1
            ]
        ]);
    }
}
