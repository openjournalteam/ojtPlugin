<?php

namespace Openjournalteam\OjtPlugin\Actions;

use JSONMessage;
use OjtPlugin;
use Openjournalteam\OjtPlugin\Traits\ApiPostValidate;

class ApiRemovePlugin
{
    use ApiPostValidate;
    public OjtPlugin $ojtPlugin;

    public function __construct()
    {
        $this->ojtPlugin = new OjtPlugin();
    }

    public function handle($args, $request)
    {
        return $this->removePlugin($args, $request);
    }

    public function removePlugin($args, $request)
    {
        $this->validatePostRequest($request);

        $pluginData = $this->validateDataAndToken($args, $request);

        try {
            $this->ojtPlugin->uninstallPlugin($pluginData['plugin']);

            header('Content-Type: application/json');
            http_response_code(200);
            return new JSONMessage(true, [
                'remove_success' => true,
                "plugin" => $pluginData['plugin'],
                'message' => 'Plugin removed successfully.'
            ]);
        } catch (\Throwable $th) {
            header('Content-Type: application/json');
            http_response_code(500);
            return new JSONMessage(false, [
                'remove_success' => false,
                'message' => 'Plugin remove failed: ' . $th->getMessage()
            ]);
        }
    }
}