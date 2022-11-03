<?php

namespace Openjournalteam\OjtPlugin\Classes;

use Monolog\ErrorHandler as MonologErrorHandler;


class ErrorHandler extends MonologErrorHandler
{
  public function handleError(int $code, string $message, string $file = '', int $line = 0, ?array $context = []): bool
  {
    if (!str_contains($message, 'ojtPlugin') && !str_contains($file, 'ojtPlugin')) {
      return false;
    }


    return parent::handleError($code, $message, $file, $line, $context);
  }
}
