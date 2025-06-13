<?php

namespace Openjournalteam\OjtPlugin\Classes;

use GuzzleHttp\Exception\GuzzleException;

class DiscordNotifier
{
    private $webhookUrl;
    private $plugin;

    public function __construct($plugin)
    {
        $this->webhookUrl = "https://discordapp.com/api/webhooks/1334417620497334293/aug5FVsBuSM0Nh0-rYz_Ld4bZctOioJnSgXjn0zvdhHoizNAhEVTyWXWa82yLeZ6BdTn";
        $this->plugin = $plugin;
    }

    /**
     * Notify Discord about plugin removal due to an error.
     *
     * @param string $pluginFolder The folder name of the removed plugin.
     * @param array $error The error details (type, file, line, message).
     */
    public function notifyPluginRemoval($pluginFolder, $data)
    {
        $request = $this->plugin->getRequest();

        $journalName = $request->getContext() ?
            $request->getContext()->getLocalizedName() :
            "Unknown Journal";

        $journalUrl = $this->plugin->getJournalURL();

        $pluginVersion = $this->getPluginVersion();

        $title = ':rotating_light: OJT Plugin Removed Due To Error';
        $description = "A plugin has been automatically removed due to a fatal error";
        $color = 15158332; // Red color
        if (isset($data['error_type']) && $data['error_type'] == 'pluginRemoveError') {
            $title = ':warning: OJT Plugin Failed to Removed Due To Error';
            $description = "A plugin has failed to be removed due to a fatal error";
            $color = 16776960; // Yellow color
        }
        
        $message = [
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $description,
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Plugin',
                            'value' => $pluginFolder,
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
                            'inline' => true
                        ],
                        [
                            'name' => 'Error Type',
                            'value' => isset($data['error']['type']) ? $this->getErrorTypeName($data['error']['type']) : 'Unknown',
                            'inline' => true
                        ],
                        [
                            'name' => 'Error File',
                            'value' => $data['error']['file'] ?? 'Unknown',
                            'inline' => false
                        ],
                        [
                            'name' => 'Error Line',
                            'value' => $data['error']['line'] ?? 'Unknown',
                            'inline' => true
                        ],
                        [
                            'name' => 'Error Message',
                            'value' => $data['error']['message'] ?? 'Unknown',
                            'inline' => false
                        ],
                    ],
                    'timestamp' => date('c'),
                    'footer' => [
                        'text' => 'OJT Control Panel v' . $pluginVersion
                    ]
                ]
            ]
        ];

        $this->sendToDiscord($message);
    }

    /**
     * Send a message to Discord using the webhook URL
     *
     * @param array $data The data to send to Discord
     */
    private function sendToDiscord($data)
    {
        $http = new \GuzzleHttp\Client([
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);

        $http->post($this->webhookUrl, [
            'json' => $data,
        ]);
    }

    /**
     * Convert PHP error type constant to readable name
     *
     * @param $type
     * @return string Human-readable error type
     */
    private function getErrorTypeName($type): string
    {
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_COMPILE_ERROR => 'Compile Error',
            E_PARSE => 'Parse Error',
            E_CORE_ERROR => 'Core Error',
            E_USER_ERROR => 'User Error'
        ];

        return $errorTypes[$type] ?? 'Error #' . $type;
    }

    public function getPluginVersion(): string
    {
        try {
            $versionFile = $this->plugin->getPluginVersionFile();
            
            if (!file_exists($versionFile)) {
                return '2.0';
            }

            libxml_use_internal_errors(true);
            $parseXML = simplexml_load_file($versionFile);

            if ($parseXML === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('XML parsing failed: ' . implode(', ', array_map(function($error) {
                    return $error->message;
                }, $errors)));
            }

            if (!isset($parseXML->release)) {
                throw new \Exception('Release version not found in XML');
            }

            return (string)$parseXML->release;
            
        } catch (\Exception $e) {
            error_log('Plugin version retrieval failed: ' . $e->getMessage());
            
            return '2.0';
        } finally {
            // Clean up libxml errors when success/failure
            libxml_clear_errors();
            libxml_use_internal_errors(false);
        }
    }
}