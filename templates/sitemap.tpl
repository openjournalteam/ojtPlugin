{assign var="pageTitle" value="$productName"|escape}
{assign var="pageTitleTranslated" value="$productName"|escape}
{assign var="pageDescription" value="$productDescription"|escape}
{include file="frontend/components/header.tpl"}

<header>
  <h1>Journal Name: {$journalName|escape}</h1>
  <section>
    <h2>ISSN Information</h2>
    <p>ISSN (online): {$onlineIssn|escape}</p>
    <p>ISSN (print): {$printIssn|escape}</p>
  </section>
</header>

<main role="main">
  <article>
    <h2>About <a href="{$journalUrl|escape}" target="_blank">{$journalName|escape}</a></h2>
    {$journalAbout}
  </article>

  <article>
    <h2>About <a href="{$productUrl|escape}" target="_blank">{$productName|escape}</a></h2>
    <p class="journal-info">
      {$productDescription}
    </p>
    {foreach from=$productLongDescription item=desc }
      {$desc}
    {/foreach}
  </article>

  {include file="{$ojtTemplatePath}"}

  <section>
    <h2>Product Information</h2>
    <dl>
      <dt>Product Name:</dt>
      <dd>{$productName|escape}</dd>
      <dt>Product Version:</dt>
      <dd>{$productVersion|escape}</dd>
      <dt>Installed On:</dt>
      <dd>{$productInstalledDate|escape}</dd>
    </dl>
  </section>
</main>


{include file="frontend/components/footer.tpl"}