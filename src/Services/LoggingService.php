<?php

namespace Openjournalteam\OjtPlugin\Services;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Utils;
use Openjournalteam\OjtPlugin\Classes\ServiceHandler;
use Psr\Log\LogLevel;
use Throwable;

class LoggingService
{
    private \OjtPlugin $plugin;

    public function __construct(\OjtPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function isAllowSendLog($hour = 4)
    {
        $now = time();
        $lastSendLogTime = $this->plugin->getSetting(CONTEXT_SITE, 'lastSendLogTime');
        if ($lastSendLogTime === null) {
            return true;
        }

        $diff = $now - $lastSendLogTime;
        $diffInHour = round($diff / (60 * 60));
        return $diffInHour >= $hour;
    }

    public static function getErrorLogFile()
    {
        return \Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'ojtPlugin' . DIRECTORY_SEPARATOR . 'error.log';
    }

    public function deleteLogFile(): bool
    {
        $errorLogFile = $this->getErrorLogFile();
        if (!is_file($errorLogFile)) return false;

        return unlink($errorLogFile);
    }

    public function isTimeToDeleteLog($days = 2)
    {
        $errorLogFile = $this->getErrorLogFile();

        if (!is_file($errorLogFile)) return false;

        $dateCreatedFile = filemtime($errorLogFile);

        $now = time();
        $datediff = $now - $dateCreatedFile;
        $diffInDays = round($datediff / (60 * 60 * 24));

        return $diffInDays > $days;
    }

    public function setLogger()
    {
        // Jangan simpan log error ketika setting ini didisable
        if (!$this->isDiagnosticEnabled()) return;

        $logger = new Logger('OJTLog');
        $logger->pushHandler(new ServiceHandler(Logger::ERROR));
        $logger->pushHandler(new StreamHandler($this->getErrorLogFile(), Logger::ERROR));

        set_exception_handler(function (Throwable $e) use ($logger): void {
            if ($this->isTimeToDeleteLog()) {
                $this->deleteLogFile();
            };

            if (ojt_str_contains($e->getFile(), 'ojtPlugin')) {
                $logger->log(
                    LogLevel::ERROR,
                    sprintf('Uncaught Exception %s: "%s" at %s line %s', Utils::getClass($e), $e->getMessage(), $e->getFile(), $e->getLine()),
                    ['exception' => $e]
                );
            }

            throw $e;
        });

        set_error_handler(function (int $code, string $message, string $file = '', int $line = 0, ?array $context = []) use ($logger): bool {
            if ($code !== E_ERROR) return false;

            if ($this->isTimeToDeleteLog()) {
                $this->deleteLogFile();
            };

            $logger->log(LogLevel::CRITICAL, 'E_ERROR: ' . $message, ['code' => $code, 'message' => $message, 'file' => $file, 'line' => $line]);
            return false;
        });
    }

    public function isDiagnosticEnabled()
    {
        return $this->plugin->getSetting(CONTEXT_SITE, 'enable_diagnostic') ?? true;
    }
}
