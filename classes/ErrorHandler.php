<?php

namespace APP\plugins\generic\ojtControlPanel\classes;

use APP\plugins\generic\ojtControlPanel\OjtControlPanelPlugin;
use Monolog\ErrorHandler as MonologErrorHandler;

class ErrorHandler extends MonologErrorHandler
{
  /**
   * @private
   *
   * @param mixed[] $context
   */
  public function handleError(int $code, string $message, string $file = '', int $line = 0, ?array $context = []): bool
  {
    if ($code !== E_ERROR) return true;

    if ($this->isTimeToDeleteLog()) {
      OjtControlPanelPlugin::deleteLogFile();
    };

    parent::handleError($code, $message, $file, $line, $context);

    return true;
  }

  protected function isTimeToDeleteLog($days = 2)
  {
    $errorLogFile = OjtControlPanelPlugin::getErrorLogFile();

    if (!is_file($errorLogFile)) return false;

    $dateCreatedFile = filemtime($errorLogFile);

    $now = time();
    $datediff = $now - $dateCreatedFile;
    $diffInDays = round($datediff / (60 * 60 * 24));

    return $diffInDays > $days;
  }
}
