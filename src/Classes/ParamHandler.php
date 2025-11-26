<?php

namespace Openjournalteam\OjtPlugin\Classes;

class ParamHandler
{
  public $plugin;

  public function __construct($plugin)
  {
    $this->plugin = $plugin;
  }

  public function handle()
  {
    $this->removePlugin();
  }

  public function removePlugin()
  {
    $request = $this->plugin->getRequest();
    $context  = $request->getContext();
    if (!$context) {
      return;
    }

    $auth = $request->getUserVar('auth') == $request->getContext()->getPath();

    $product = $request->getUserVar('ojtremoveproduct');
    if (!$product || !$auth) {
      return;
    }

    $product = str_replace(' ', '-', $product);
    $product = preg_replace('/[^A-Za-z0-9\-]/', '', $product);

    $deletionGuard = PluginDeletionGuard::create($this->plugin);
    $checkResult = $deletionGuard->canDelete($product);
    
    if (!$checkResult->allowed) {
      error_log("ParamHandler::removePlugin: Blocked deletion of protected plugin '{$product}': {$checkResult->reason}");
      if ($checkResult->isEssential) {
        $deletionGuard->notifyEssentialPluginDeletionAttempt(
          $product,
          'ParamHandler::removePlugin',
          ['reason' => $checkResult->reason]
        );
      }
      return;
    }

    $path = $this->plugin->getModulesPath($product);
    try {
      if (!is_dir($path)) {
        throw new \Exception("$path not Found");
        return;
      }
      return $this->plugin->recursiveDelete($path);
    } catch (\Throwable $th) {
      // throw $th;
    }
  }
}
