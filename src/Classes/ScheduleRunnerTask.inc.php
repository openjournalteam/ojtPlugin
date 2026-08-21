<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ScheduleRunnerTask extends ScheduledTask
{
    public function getName()
    {
        return 'OJT scheduled job runner';
    }

    protected function executeActions()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'ojtPlugin');
        if (!$plugin) {
            PluginRegistry::loadCategory('generic', true);
            $plugin = PluginRegistry::getPlugin('generic', 'ojtPlugin');
        }

        if (!$plugin || !method_exists($plugin, 'scheduleService')) {
            $this->addExecutionLogEntry('OJT Control Panel is not available.');
            return false;
        }

        $summary = $plugin->scheduleService()->runDueSchedules();
        $this->addExecutionLogEntry('Schedules checked: ' . (int) $summary['checked']);
        $this->addExecutionLogEntry('Jobs dispatched: ' . (int) $summary['dispatched']);
        $this->addExecutionLogEntry('Dispatch failures: ' . (int) $summary['failed']);

        foreach (array_slice((array) $summary['errors'], 0, 5) as $error) {
            $this->addExecutionLogEntry('Error: ' . $error);
        }

        return empty($summary['errors']);
    }
}
