<?php

namespace Openjournalteam\OjtPlugin\Actions;

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
            $uninstallPlugin = $this->ojtPlugin->uninstallPlugin($pluginData['plugin']);
            if (!$uninstallPlugin) {
                throw new \Exception('Plugin uninstall failed.');
            }
            
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'remove_success' => true,
                'message' => 'Plugin removed successfully.',
            ]);
            exit;
        } catch (\Throwable $th) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'remove_success' => false,
                'message' => 'Plugin remove failed: ' . $th->getMessage() . ' on line ' . $th->getLine() . ' in ' . $th->getFile()
            ]);
            exit;
        }
    }
}