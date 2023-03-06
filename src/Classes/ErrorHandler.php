<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Monolog\ErrorHandler as MonologErrorHandler;
use OjtPlugin;

class ErrorHandler extends MonologErrorHandler
{
  /**
   * @private
   *
   * @param mixed[] $context
   */
  public function handleError(int $code, string $message, string $file = '', int $line = 0, ?array $context = []): bool
  {
    if ($this->isTimeToDeleteLog()) {
      OjtPlugin::deleteLogFile();
    };

    parent::handleError($code, $message, $file, $line, $context);

    return true;
  }

  protected function isTimeToDeleteLog($days = 2)
  {
    $errorLogFile = OjtPlugin::getErrorLogFile();

    if (!is_file($errorLogFile)) return false;

    $dateCreatedFile = filemtime($errorLogFile);

    $now = time();
    $datediff = $now - $dateCreatedFile;
    $diffInDays = round($datediff / (60 * 60 * 24));

    return $diffInDays > $days;
  }
}
