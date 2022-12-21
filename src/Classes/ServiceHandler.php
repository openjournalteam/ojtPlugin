<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Monolog\Handler\AbstractProcessingHandler;
use OjtPlugin;

class ServiceHandler extends AbstractProcessingHandler
{
  protected function write(array $record): void
  {
    try {
      $url = 'https://sp.openjournaltheme.com/api/v1/report';
      $ojtPlugin = OjtPlugin::get();
      if (!$ojtPlugin->isAllowSendLog() || getcwd() == '/') {
        return;
      }

      $request = &\Registry::get('request');
      $logFile = OjtPlugin::getErrorLogFile();
      $multipart = [
        [
          'name' => 'name',
          'contents' => 'OJTPlugin'
        ],
        [
          'name' => 'email',
          'contents' => 'error@openjournaltheme.com'
        ],
        [
          'name' => 'plugin',
          'contents' => 'ojtPlugin'
        ],
        [
          'name' => 'problem',
          'contents' => $record['message'],
        ],
        [
          'name' => 'type',
          'contents' => 'report_bug'
        ],
        [
          'name' => 'ip',
          'contents' => $request->getRemoteAddr()
        ]
      ];

      if (file_exists($logFile)) {
        $multipart[] = [
          'name' => 'log',
          'filename' => 'error.log',
          'contents' => file_get_contents($logFile),
          'headers' => [
            // 'Content-Type' => mime_content_type($logFile)
          ]
        ];
      }

      $client = $ojtPlugin->getHttpClient([
        'Accept'     => 'application/json',
      ]);

      $client->post($url, [
        'multipart' => $multipart
      ]);
    } catch (\Throwable $th) {
    }

    $ojtPlugin->updateSetting(CONTEXT_SITE, 'lastSendLogTime', time());
  }
}
