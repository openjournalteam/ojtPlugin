<?php

namespace Openjournalteam\OjtPlugin\Classes;

use GuzzleHttp\Exception\GuzzleException;

class DiscordNotifier
{
    private $webhookUrl;
    private $plugin;

    public function __construct($plugin)
    {
        $this->webhookUrl = "https://discord.com/api/webhooks/1346650429294903356/nipYAe1hISDiGlil0AB365Yur2qEp8Qyn5Y6G7ElVggOfeQD9nrhmFzsYK4Y5t8VLp0p";
        $this->plugin = $plugin;
    }

    /**
     * Notify Discord about plugin removal due to an error.
     *
     * @param string $pluginFolder The folder name of the removed plugin.
     * @param array $error The error details (type, file, line, message).
     */
    public function notifyPluginRemoval($pluginFolder, $error)
    {
        $request = $this->plugin->getRequest();

        $journalName = $request->getContext() ?
            $request->getContext()->getLocalizedName() :
            "Unknown Journal";

        $journalUrl = $this->plugin->getJournalURL();

        $pluginVersion = $this->getPluginVersion();
        
        $message = [
            'embeds' => [
                [
                    'title' => ':rotating_light: OJT Plugin Removed Due To Error',
                    'description' => "A plugin has been automatically removed due to a fatal error",
                    'color' => 15158332, // Red color
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
                            'value' => isset($error['type']) ? $this->getErrorTypeName($error['type']) : 'Unknown',
                            'inline' => true
                        ],
                        [
                            'name' => 'Error File',
                            'value' => $error['file'] ?? 'Unknown',
                            'inline' => false
                        ],
                        [
                            'name' => 'Error Line',
                            'value' => $error['line'] ?? 'Unknown',
                            'inline' => true
                        ],
                        [
                            'name' => 'Error Message',
                            'value' => $error['message'] ?? 'Unknown',
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
        libxml_use_internal_errors(true);
        $versionFile = $this->plugin->getPluginVersionFile();
        $parseXML = false;

        if (file_exists($versionFile)) {
            $parseXML = simplexml_load_file($versionFile);
            if ($parseXML === false) {
                // XML parsing failed, collect errors
                $errors = libxml_get_errors();
                libxml_clear_errors();
                // You could log these errors if needed
            }
        }

        // Use a fallback version if XML parsing failed
        $pluginVersion = ($parseXML && isset($parseXML->release)) ? (string)$parseXML->release : '2.0';

        return $pluginVersion;
    }
}