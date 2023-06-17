# Upgrade Plugin OJS untuk Support Versi OJS 3.4

## TODO

### Upgrade Plugins dan Tema

- [ ] Novelty
- [ ] OJT Plus

Silahkan baca [catatan release](https://docs.pkp.sfu.ca/dev/release-notebooks/en/3.4-release-notebook) oleh PKP terlebih dahulu untuk memahami perubahan apa saja yang ada di OJS versi 3.4

## Namespaces and Constant

Dikarenakan semua class sudah menggunakan namespace dan sudah diautoload oleh composer. Maka fungsi global `import()` tidak diperlukan lagi.
Nama file yang sebelumnya ada suffix `*.inc.php` sekarang dihilangkan suffixnya menjadi `*.php` saja.

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

## HookRegistry berubah menjadi \PKP\plugins\Hook

`HookRegistry` dinyatakan deprecated. dan nanti di versi berikutnya akan dihapus. Ubah kode ini:

```php
HookRegistry::register('...', function($hookName, $args) {});
HookRegistry::call('...', [$a, $b]);
```

Dengan kode ini :

```php
use PKP\plugins\Hook;

Hook::add('...', function($hookName, $args) {});
Hook::call('...', [$a, $b]);
```

## Smarty

Semua `const` yang ada dismarty variable `$smarty` sudah tidak valid lagi. Contohnya :

```php
<a href='{url router=$smarty.const.ROUTE_PAGE page="announcement" op="view" path=$announcement->getId()}'>
```

Menjadi :

```php
<a href='{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="announcement" op="view" path=$announcement->getId()}'>
```

### List yang berubah

```
$smarty.const.PUBLISHING_MODE_NONE
\APP\journal\Journal::PUBLISHING_MODE_NONE

$smarty.const.PUBLISHING_MODE_OPEN
\APP\journal\Journal::PUBLISHING_MODE_OPEN

$smarty.const.ARTICLE_ACCESS_OPEN
\APP\submission\Submission::ARTICLE_ACCESS_OPEN

$smarty.const.AUTHOR_TOC_DEFAULT
\APP\submission\Submission::AUTHOR_TOC_DEFAULT

$smarty.const.AUTHOR_TOC_SHOW
\APP\submission\Submission::AUTHOR_TOC_SHOW

$smarty.const.STATUS_PUBLISHED
\PKP\submission\PKPSubmission::STATUS_PUBLISHED

$smarty.const.CONTEXT_ID_NONE
\PKP\core\PKPApplication::CONTEXT_ID_NONE
```

### Submission

Ada beberapa method yang sudah dihilangkan atau dipindahkan ke `Publication` di class `Submission` .
Berikut List nya:

```php
$article->getLocalizedCoverImage()
// menjadi
$publication->getLocalizedData('coverImage')

$article->getAuthors()
// menjadi
$publication->getData('authors')

$article->getAuthorString()
// menjadi
$publication->getAuthorString($authorUserGroups)

$article->getLocalizedCoverImageUrl()
// menjadi
$publication->getLocalizedCoverImageUrl($article->getData('contextId'))

$article->getLocalizedCoverImageAltText()
// fungsi dihilangkan, alternatifnya gunakan
{assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
// kemudian akses dengan
{$coverImage.altText|escape|default:''}
```

### LoadHandler Hook

Ada perubahan cara load handler. Jika sebelumnya kita menggunakan kode ini:

```php
public function setPageHandler($hookName, $params)
{
    $page = $params[0];

    if ($page == $this->getPluginName()) {
        $this->import('NoveltyThemePluginHandler');
        define('HANDLER_CLASS', 'NoveltyThemePluginHandler');
        return true;
    }

    return false;
}
```

Maka sekarang kita harus menggunakan kode ini:

```php
public function setPageHandler($hookName, $params)
{
    $page = $params[0];
    $handler = &$params[3];


    if ($page == $this->getPluginName()) {
        $handler = new NoveltyThemePluginHandler();
        return true;
    }

    return false;
}
```

## DAO

Penggunaan DAO sudah tidak dianjurkan lagi, jika terdapat class `Repository` yang terkait dengan entity yang ingin diakses, maka gunakan `Repository` tersebut. Jika tidak ada, maka gunakan `DAO` yang terkait dengan entity tersebut. [Baca Catatan Rilis](https://docs.pkp.sfu.ca/dev/release-notebooks/en/3.4-release-notebook#repositories)

`CategoryDAO` sudah tidak digunakan lagi, sebagai gantinya gunakan `Repo::category()`. Silahkan pelajari cara penggunakan `Repo` di [sini](https://docs.pkp.sfu.ca/dev/documentation/en/architecture-repositories)
