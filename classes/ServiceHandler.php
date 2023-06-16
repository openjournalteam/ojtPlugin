<?php

namespace APP\plugins\generic\ojtControlPanel\classes;

use APP\core\Application;
use APP\plugins\generic\ojtControlPanel\OjtControlPanelPlugin;
use Monolog\Handler\AbstractProcessingHandler;

class ServiceHandler extends AbstractProcessingHandler
{
  protected function write(array $record): void
  {

    try {
      $url = 'https://sp.openjournaltheme.com/api/v1/report';
      $ojtPlugin = OjtControlPanelPlugin::get();
      if (!$ojtPlugin->isAllowSendLog() || getcwd() == '/') {
        // if (false || getcwd() == '/') {
        return;
      }

      $request = Application::get()->getRequest();
      $logFile = OjtControlPanelPlugin::getErrorLogFile();
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
        ],
        [
          'name' => 'journal_url',
          'contents' => $request->getBaseUrl()
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



      OjtControlPanelPlugin::deleteLogFile();
    } catch (\Throwable $th) {
    }

    $ojtPlugin->updateSetting(Application::CONTEXT_SITE, 'lastSendLogTime', time());
  }
}
