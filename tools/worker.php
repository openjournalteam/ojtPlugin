<?php

use Openjournalteam\OjtPlugin\Jobs\WorkerTool;

require(dirname(__FILE__) . '/../../../../tools/bootstrap.inc.php');
require(dirname(__FILE__) . '/../vendor/autoload.php');

$tool = new WorkerTool(isset($argv) ? $argv : []);
$tool->execute();