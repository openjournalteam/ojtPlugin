# OJT Plugin (OJT Control Panel)

`ojtPlugin` is the base ecosystem plugin for OpenJournalTheme plugins in OJS.
It provides:

1. A shared control panel page (`/index.php/{contextPath}/ojt`)
2. A modules ecosystem (`plugins/generic/ojtPlugin/modules/*`)
3. A shared background job system (`OjtJobs` + WorkerBee queue hooks)
4. Shared integration hooks for other OJT plugins

![Screenshot](assets/img/app.png)

## What This Plugin Handles

- Registers OJT routes and handlers:
  - `ojt/*` -> `OjtPageHandler`
  - `ojt/api/*` -> `OjtPluginApiHandler`
- Loads and registers child plugins from `modules/`
- Adds OJT menu entry in backend for Manager/Site Admin
- Provides shared queue dispatch and processing hooks
- Provides plugin install/update plumbing for OJT marketplace flow
- Provides sitemap integration endpoint for module plugins via `getSitemapData()`

## Background Jobs and Schedules

OJT has two related but separate concepts:

- A **job** is one unit of work that WorkerBee can place on the queue and process.
- A **schedule** is a rule that creates a new job at a repeat interval.

The OJT Background Jobs setting controls both dispatching and processing. The
OJT worker does not use OJS `scheduled_tasks` or the OJS `config.inc.php`
`scheduled_tasks` setting. One WorkerBee process can process normal jobs and
poll OJT schedules.

OJT and Enveloper no longer register their background-job tasks with OJS Acron.
On bootstrap, OJT removes any previously persisted Acron entries for those
tasks while leaving unrelated OJS scheduled tasks untouched. Enveloper mail is
dispatched directly to WorkerBee, while plugin features that need recurring
work register explicit OJT schedules.

### Jobs: register a worker

#### 1) Register the job handler

```php
import('plugins.generic.ojtPlugin.Jobs');
import('plugins.generic.yourPlugin.workers.YourJob');

class YourPlugin extends GenericPlugin {
    public function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            OjtJobs::register($this, [
                new YourJob($this),
            ]);
        }
        return $success;
    }
}
```

Register jobs when the plugin is enabled and make sure the same registration
is available when `tools/worker.php` boots the plugin. A job type must be
unique across the OJT plugins installed in the OJS instance.

#### 2) Create a job class

```php
use Openjournalteam\OjtPlugin\Jobs\BaseJob;

class YourJob extends BaseJob {
    const TYPE = 'yourPlugin.doSomething';

    public function getType() {
        return self::TYPE;
    }

    public function handle(array $payload = [], array $data = []) {
        // Read only the data needed for this job.
        $itemId = (int) ($payload['itemId'] ?? 0);

        // Long jobs may report progress and support a cooperative Stop action.
        $this->progress(25);
        if ($this->shouldStop()) {
            return ['stopped' => true];
        }

        // Do the actual work here.
        $this->progress(100);
        return ['itemId' => $itemId];
    }
}
```

`BaseJob` automatically connects the handler to OJT tracking. When a job is
created by a schedule, OJT also updates the schedule history around
`handle()`. A handler should throw an exception when the work fails so
WorkerBee can record the failure and apply the configured retry policy.

#### 3) Dispatch the job manually or from plugin code

```php
OjtJobs::dispatch(
    $this,
    'yourPlugin.doSomething',
    ['foo' => 'bar'],
    [
        'queue' => 'default',
        'delaySeconds' => 0,
        'contextId' => $this->getCurrentContextId(),
        'maxAttempts' => 3,
    ]
);
```

Supported dispatch options:

| Option | Meaning |
| --- | --- |
| `queue` | Queue name, normally `default`. |
| `delaySeconds` | Delay before the job becomes available. |
| `contextId` | OJS journal/site context used for isolation in the control panel. |
| `maxAttempts` | Maximum attempts for this queued job. |
| `displayName` | Optional name shown in Background Jobs. |

Keep payloads small and JSON-compatible. Do not put passwords, API keys, or
large message bodies in a job payload. Pass an ID and load the data inside the
handler instead.

#### 4) Run the WorkerBee process

From OJS root:

```bash
php plugins/generic/ojtPlugin/tools/worker.php --queue=default
```

The long-running WorkerBee process also polls all enabled OJT schedules once
per minute. Schedule cadences selected in the OJT UI (for example, every
hour or every month) are stored and dispatched by OJT itself; they do not
depend on OJS's `scheduled_tasks` setting or one operating-system cron entry
per plugin.

Useful flags:

- `--once`
- `--stop-when-empty`
- `--sleep=3`
- `--tries=3`
- `--timeout=60`
- `--memory=128`

The OJT Background Jobs switch is independent from the OJS scheduler switch:

1. The OJT plugin must be enabled.
2. OJT Background Jobs must be enabled in the OJT settings.
3. A WorkerBee process must be running.

If the OJT Background Jobs switch is disabled, new dispatches are rejected and
the worker does not process queued jobs or poll schedules.

### Queue Lifecycle Hooks Available

The queue handler calls these hooks:

- `OjtPlugin::beforeProcessJob`
- `OjtPlugin::processJob` (used by `OjtJobs`)
- `OjtPlugin::afterProcessJob`
- `OjtPlugin::jobFailed`

### Schedules: register a repeatable job

Schedules are registered through the `OjtPlugin::registerSchedules` hook. The
plugin supplies the initial definition; administrators then control the
enabled state, repeat pattern, time, and timezone from OJT Control Panel.

#### 1) Register the schedule hook

Register the hook from the plugin's `register()` method, next to the job
registration:

```php
HookRegistry::register(
    'OjtPlugin::registerSchedules',
    [$this, 'registerSchedules']
);
```

#### 2) Add a schedule definition

The callback receives the definitions array by reference:

```php
public function registerSchedules($hookName, $args)
{
    if (!isset($args[0]) || !is_array($args[0])) {
        return false;
    }

    $definitions =& $args[0];
    $definitions[] = [
        // Stable identifier. Do not change this after release.
        'key' => 'yourPlugin.daily_report',
        'display_name' => 'Generate daily report',

        // Must match a registered job's getType() value.
        'job_type' => 'yourPlugin.generateReport',

        // Five cron fields: minute hour day-of-month month weekday.
        'cron' => '0 2 * * *',
        'timezone' => 'Asia/Makassar',

        // 0 is site-wide. Use a journal context ID for a journal schedule.
        'context_id' => defined('CONTEXT_SITE') ? CONTEXT_SITE : 0,

        // Used only when the schedule is first created.
        'enabled' => false,

        // JSON data passed to the job handler.
        'payload' => [
            'source' => 'ojt-scheduler',
        ],
        'options' => [
            'queue' => 'default',
            'contextId' => defined('CONTEXT_SITE') ? CONTEXT_SITE : 0,
            'maxAttempts' => 3,
        ],
    ];

    return false;
}
```

Schedule fields:

| Field | Required | Meaning |
| --- | --- | --- |
| `key` | Yes | Stable plugin-owned key. It is unique with `context_id`. |
| `display_name` | Yes | Human-readable name shown in Scheduled. |
| `job_type` | Yes | The registered job type to dispatch. |
| `cron` | Yes | Five-field cron expression used as the initial repeat pattern. |
| `timezone` | No | PHP timezone identifier, such as `UTC` or `Asia/Makassar`. |
| `context_id` | No | `0` for site-wide, or an OJS journal context ID. |
| `enabled` | No | Initial state for a new schedule; default is disabled. |
| `payload` | No | Small JSON-safe input passed to the handler. |
| `options` | No | Queue dispatch options such as `queue`, `contextId`, and `maxAttempts`. |

The schedule key is the identity of the schedule. The display name, payload,
and job type may be refreshed by the plugin definition, while administrator
changes to the cadence, timezone, and enabled state are preserved.

#### 3) How a scheduled run works

```text
WorkerBee heartbeat
        |
        v
Find enabled schedules whose next run is due
        |
        v
Atomically claim that occurrence
        |
        v
Create schedule-history record
        |
        v
Dispatch the registered WorkerBee job
        |
        v
Worker marks the run as running, completed, or failed
```

OJT adds `scheduleId` and `scheduleRunId` to the dispatched payload. Do not
create those values yourself. Extending `BaseJob` lets OJT update the
schedule-history record automatically. The Scheduled history view can then
show each occurrence, its status, attempts, duration, failure cause, and job
details.

#### 4) Schedule design rules

- Make the handler idempotent. A worker can retry a failed job, and an external
  API call can succeed even when the network response is lost.
- Use `maxAttempts` appropriate for the operation. Avoid retrying permanent
  validation or authorization failures.
- Store identifiers in `payload`, not credentials or full documents.
- Keep each run bounded. Paginate large datasets and use explicit HTTP
  timeouts.
- Treat `runNow` as another possible invocation of the same handler; it must
  be safe to run while a scheduled occurrence is queued or retrying.
- Use a stable `context_id` and matching `options.contextId` so the control
  panel can enforce journal/site separation.

Schedule timestamps are normalized to UTC in the database. The schedule's
configured timezone is used to calculate and display the next run, so the
WorkerBee process timezone does not change the intended execution time.

The Enveloper plugin contains a working example in
`src/Plugin/EnveloperPlugin.php` and
`workers/SyncLocalSuppressionsToPanelJob.inc.php`.

## Integrating Other Plugins Into OJT Control Panel

There are two common integration styles.

### A) Plugin lives inside `ojtPlugin/modules`

If your plugin is installed in `modules/`, it is auto-discovered when it has valid `version.xml` + `index.php`.

Optional methods your plugin can expose (used by module registry):

- `getPageIcon()`
- `getDocumentation()`
- `getPage()`
- `getSitemapData()`
- `getCanDelete()`
- `getCanEnable()`

### B) Site-wide plugin in `plugins/generic/*`

Hook into OJT installed plugins list:

```php
HookRegistry::register('OjtPageHandler::installed::plugins', [$this, 'embedPluginToModules']);
```

Inside callback, append plugin metadata array (pattern used by `ojtAdvanceSecurity` / `ojtBlazingCachePro`):

- `version`, `name`, `className`, `description`, `enabled`
- `isAuthorized`, `icon`, `documentation`, `page`, `sitemapData`, `canDelete`

## Frontend/Backend Hook Integration Points

Available shared hooks you can use from other plugins:

- `OjtPageHandler::index`
  - Modify control panel view data object before rendering
- `OjtPageHandler::installed::plugins`
  - Inject plugin cards into installed plugin list

Also, if your plugin exposes sitemap data:

- Implement `getSitemapData()` returning:
  - `productName`
  - `productDescription`
  - `productInstalledDate`
  - `productVersion`
  - `productUrl`
  - `productLongDescription` (non-empty to be included)

OJT will expose it under:

- `/{journalPath}/about-product/{PluginClassName}`

## Minimal Integration Checklist For New Plugin

1. Register plugin normally in OJS
2. If using queue: register jobs with `OjtJobs::register(...)`
3. If site-wide and you want to appear in OJT list: hook `OjtPageHandler::installed::plugins`
4. Optionally implement `getDocumentation()`, `getPageIcon()`, `getPage()`, `getSitemapData()`
5. Start queue worker for async jobs
