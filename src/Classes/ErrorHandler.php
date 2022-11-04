<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Monolog\ErrorHandler as MonologErrorHandler;
use OjtPlugin;

class ErrorHandler extends MonologErrorHandler
{
  public function handleError(int $code, string $message, string $file = '', int $line = 0, ?array $context = []): bool
  {
    if (!str_contains($message, 'ojtPlugin') && !str_contains($file, 'ojtPlugin')) {
      return false;
    }

    $this->deleteLogInDays();

    parent::handleError($code, $message, $file, $line, $context);

    return false;
  }

  protected function isTimeToDeleteLog($days = 7)
  {
    $ojt = OjtPlugin::get();
    $lastDelete = $ojt->getSetting(CONTEXT_SITE, 'lastDeleteLog');
    if (!$lastDelete) {
      $ojt->updateSetting(CONTEXT_SITE, 'lastDeleteLog', time());
      $lastDelete = $ojt->getSetting(CONTEXT_SITE, 'lastDeleteLog');
    }

    $now = time();
    $datediff = $now - $lastDelete;
    $diffInDays = round($datediff / (60 * 60 * 24));
    return $diffInDays > $days;
  }

  public function deleteLogInDays($days = 7): void
  {
    $ojt = OjtPlugin::get();
    $errorLogFile = OjtPlugin::getErrorLogFile();
    if (!$this->isTimeToDeleteLog($days) || !is_file($errorLogFile)) return;

    unlink($errorLogFile);

    $ojt->updateSetting(CONTEXT_SITE, 'lastDeleteLog', time());
  }
}
