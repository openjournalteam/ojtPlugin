<?php

use Openjournalteam\OjtPlugin\Jobs\WorkerTool;

require(dirname(__FILE__) . '/../../../../../../tools/bootstrap.inc.php');

$tool = new WorkerTool(isset($argv) ? $argv : []);
$tool->execute();