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

    if ($this->isTimeToDeleteLog()) {
      $this->deleteLogFile();
    };

    parent::handleError($code, $message, $file, $line, $context);

    return false;
  }

  protected function isTimeToDeleteLog($days = 3)
  {
    $errorLogFile = OjtPlugin::getErrorLogFile();

    if (!is_file($errorLogFile)) return false;

    $dateCreatedFile = filemtime($errorLogFile);

    $now = time();
    $datediff = $now - $dateCreatedFile;
    $diffInDays = round($datediff / (60 * 60 * 24));

    return $diffInDays > $days;
  }

  protected function deleteLogFile(): bool
  {
    $errorLogFile = OjtPlugin::getErrorLogFile();
    if (!is_file($errorLogFile)) return false;


    return unlink($errorLogFile);
  }
}
