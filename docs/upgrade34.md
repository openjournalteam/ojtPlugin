# Upgrade Plugin OJS untuk Support Versi OJS 3.4

## TODO

### Upgrade Plugins dan Tema

- [ ] Novelty
- [ ] OJT Plus

Silahkan baca [catatan release](https://docs.pkp.sfu.ca/dev/release-notebooks/en/3.4-release-notebook) oleh PKP terlebih dahulu untuk memahami perubahan apa saja yang ada di OJS versi 3.4

## Namespaces and Constant

Dikarenakan semua class sudah menggunakan namespace dan sudah diautoload oleh composer. Maka fungsi global `import()` tidak diperlukan lagi:

```php
import('classes.submission.Submission');
import('lib.pkp.classes.author.PKPAuthor');

$submission = new Submission();
$author = new PKPAuthor();

$submission->setData('status', STATUS_PUBLISHED);
```

Ganti kode ini dengan `use` statements dan class constants:

```php
use APP\submission\Submission;
use PKP\author\Author;

$submission = new Submission();
$author = new PKPAuthor();

$submission->setData('status', Submission::STATUS_PUBLISHED);
```

Beberapa constants yg sering dipakai diplugin:

```php
ROUTE_PAGE
// menjadi
Application::ROUTE_PAGE

CONTEXT_SITE
// menjadi
Application::CONTEXT_SITE

ASSOC_TYPE_USER_ROLES
// menjadi
Application::ASSOC_TYPE_USER_ROLES

ROLE_ID_MANAGER
// menjadi
Role::ROLE_ID_MANAGER

ROLE_ID_SITE_ADMIN
// menjadi
Role::ROLE_ID_SITE_ADMIN

```

## HookRegistry berubah menjadi PKP\plugins\Hook

`HookRegistry` dinyatakan deprecated. dan nanti di versi berikutnya akan dihapus. Ubah kode ini:

```php
HookRegistry::register('...', function($hookName, $args) {});
HookRegistry::call('...', [$a, $b]);
```

Dengan koden ini :

```php
use PKP\plugins\Hook;

Hook::add('...', function($hookName, $args) {});
Hook::call('...', [$a, $b]);
```
