<?php

namespace Openjournalteam\OjtPlugin\Classes;

class PluginDeletionGuard
{
    const ESSENTIAL_PLUGIN_FOLDERS = [
        'ojtAdvanceSecurity',
        'ojtPlugin',
        // 'ojtBlazingCache',
    ];

    const DISCORD_WEBHOOK_URL = 'https://discordapp.com/api/webhooks/1334417620497334293/aug5FVsBuSM0Nh0-rYz_Ld4bZctOioJnSgXjn0zvdhHoizNAhEVTyWXWa82yLeZ6BdTn';

    private $plugin;

    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;
    }

    /**
     * Check if a plugin can be deleted.
     * 
     * This is the main entry point for all deletion checks. It returns a
     * DeletionCheckResult object containing the decision and reason.
     * 
     */
    public function canDelete(string $pluginFolder, $pluginInstance = null)
    {
        try {
            $pluginFolder = $this->normalizePluginFolder($pluginFolder);

            if (empty($pluginFolder)) {
                return new DeletionCheckResult(
                    false,
                    'Invalid plugin folder name',
                    false
                );
            }

            // Step 1: Check essential plugins list (highest priority, always block)
            if ($this->isEssentialPlugin($pluginFolder)) {
                return new DeletionCheckResult(
                    false,
                    "Plugin '{$pluginFolder}' is an essential system plugin and cannot be deleted",
                    true
                );
            }

            // Step 2: Advisory check - consult plugin's getCanDelete() if available
            if ($pluginInstance !== null) {
                $advisoryResult = $this->checkAdvisoryCanDelete($pluginInstance, $pluginFolder);
                if ($advisoryResult !== null && !$advisoryResult->allowed) {
                    return $advisoryResult;
                }
            }

            // Step 3: Plugin is not essential and passed advisory check (if any)
            return new DeletionCheckResult(
                true,
                'Plugin can be deleted',
                false
            );

        } catch (\Throwable $e) {
            // Fail-safe: on any error, deny deletion and log
            error_log("PluginDeletionGuard error for '{$pluginFolder}': " . $e->getMessage());
            
            return new DeletionCheckResult(
                false,
                'Unable to verify deletion safety: ' . $e->getMessage(),
                false
            );
        }
    }

    /**
     * Check if a plugin folder is in the essential plugins list
     * 
     */
    public function isEssentialPlugin(string $pluginFolder)
    {
        $normalizedFolder = strtolower($pluginFolder);
        $essentialFolders = array_map('strtolower', self::ESSENTIAL_PLUGIN_FOLDERS);
        
        return in_array($normalizedFolder, $essentialFolders, true);
    }

    /**
     * Get the list of essential plugin folders
     * 
     */
    public static function getEssentialPluginFolders()
    {
        return self::ESSENTIAL_PLUGIN_FOLDERS;
    }

    /**
     * Normalize plugin folder name by removing path components
     * 
     */
    private function normalizePluginFolder(string $pluginFolder)
    {
        $pluginFolder = basename(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pluginFolder));
        return trim($pluginFolder);
    }

    /**
     * Advisory check using plugin's getCanDelete() method
     * 
     * This is treated as advisory only. If the method doesn't exist,
     * throws an error, or returns unexpected values, we allow deletion
     * (for non-essential plugins only).
     * 
     */
    private function checkAdvisoryCanDelete($pluginInstance, string $pluginFolder)
    {
        try {
            if (!is_object($pluginInstance)) {
                return null;
            }

            if (!method_exists($pluginInstance, 'getCanDelete')) {
                return null;
            }

            $canDelete = $pluginInstance->getCanDelete();

            // Only block if explicitly returns false
            if ($canDelete === false) {
                return new DeletionCheckResult(
                    false,
                    "Plugin '{$pluginFolder}' has disabled deletion via getCanDelete()",
                    false
                );
            }

            return null;

        } catch (\Throwable $e) {
            error_log("Advisory getCanDelete() check failed for '{$pluginFolder}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send Discord notification for suspicious deletion attempt on essential plugin
     * 
     */
    public function notifyEssentialPluginDeletionAttempt(string $pluginFolder, string $source, array $additionalData = []): bool
    {
        try {
            $journalName = 'Unknown Journal';
            $journalUrl = 'Unknown URL';
            $pluginVersion = '2.0';

            if ($this->plugin !== null) {
                try {
                    $request = $this->plugin->getRequest();
                    if ($request && $request->getContext()) {
                        $journalName = $request->getContext()->getLocalizedName();
                    }
                    $journalUrl = method_exists($this->plugin, 'getJournalURL') 
                        ? $this->plugin->getJournalURL() 
                        : $journalUrl;
                    $pluginVersion = method_exists($this->plugin, 'getPluginVersion') 
                        ? $this->plugin->getPluginVersion() 
                        : $pluginVersion;
                } catch (\Throwable $e) {
                    // Ignore context errors
                }
            }

            $fields = [
                [
                    'name' => 'Plugin Folder',
                    'value' => $pluginFolder,
                    'inline' => true
                ],
                [
                    'name' => 'Deletion Source',
                    'value' => $source,
                    'inline' => true
                ],
                [
                    'name' => 'Journal',
                    'value' => $journalName,
                    'inline' => true
                ],
                [
                    'name' => 'Journal URL',
                    'value' => $journalUrl,
                    'inline' => false
                ],
            ];

            // Add error information if provided
            if (isset($additionalData['error']) && is_array($additionalData['error'])) {
                $error = $additionalData['error'];
                if (isset($error['file'])) {
                    $fields[] = [
                        'name' => 'Error File',
                        'value' => $error['file'],
                        'inline' => false
                    ];
                }
                if (isset($error['line'])) {
                    $fields[] = [
                        'name' => 'Error Line',
                        'value' => (string)$error['line'],
                        'inline' => true
                    ];
                }
                if (isset($error['message'])) {
                    $fields[] = [
                        'name' => 'Error Message',
                        'value' => substr($error['message'], 0, 1000), // Limit length
                        'inline' => false
                    ];
                }
            }

            $message = [
                'embeds' => [
                    [
                        'title' => ':shield: Essential Plugin Deletion Blocked',
                        'description' => "An attempt to delete an essential/protected plugin was blocked by the deletion guard.",
                        'color' => 15844367, // Orange/warning color
                        'fields' => $fields,
                        'timestamp' => date('c'),
                        'footer' => [
                            'text' => 'OJT Control Panel v' . $pluginVersion . ' | Deletion Guard'
                        ]
                    ]
                ]
            ];

            return $this->sendToDiscord($message);

        } catch (\Throwable $e) {
            error_log('Failed to send Discord notification for essential plugin deletion attempt: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to Discord webhook
     * 
     */
    private function sendToDiscord(array $data)
    {
        try {
            $webhookUrl = self::DISCORD_WEBHOOK_URL;

            if (empty($webhookUrl)) {
                return false;
            }

            $http = new \GuzzleHttp\Client([
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $http->post($webhookUrl, [
                'json' => $data,
            ]);

            return true;

        } catch (\Throwable $e) {
            error_log('PluginDeletionGuard Discord notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Static factory method for quick checks
     * 
     */
    public static function create($plugin = null)
    {
        return new self($plugin);
    }
}


/**
 * Value object representing the result of a deletion check
 */
class DeletionCheckResult
{
    public $allowed;
    public $reason;
    public $isEssential;

    public function __construct(bool $allowed, string $reason, bool $isEssential)
    {
        $this->allowed = $allowed;
        $this->reason = $reason;
        $this->isEssential = $isEssential;
    }
}
