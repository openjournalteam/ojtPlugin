{include file="{$templatePath}:sitemap/header.tpl"}

<header role="banner">
  <h1>Journal Name: {$journalName|escape}</h1>
  <div class="journal-identifiers">
    <h2>ISSN Information</h2>
    <h3>ISSN (online): {$onlineIssn|escape}</h3>
    <h3>ISSN (print): {$printIssn|escape}</h3>
  </div>
</header>

<main role="main">
  <article>
    <h2>About <a href="{$productUrl|escape}">{$productName|escape}</a></h2>
    <p class="product-info">
      OJT Plus is a plugin for OJS that adds extended features for your journal, such as video abstracts, submission
      date adjustments, improved performance, and more. New features are added regularly.
    </p>
  </article>

  <article>
    <h2>About <a href="https://openjournaltheme.com">Openjournaltheme</a></h2>
    <div class="company-info">
      <p> OJT team has partnered with global publishers from small to leading publishers to help them handle technical
        aspect of OJS and improve their indexing rank since 2016. Our vision is to allow any publisher to focus on their
        academic research and improve its reach rather than hurdled by the non-essential technical issue.
        Hundreds of reputable publishers from Q1 rank to newcomers trust and collaborate with our team to achieve their
        best potential, it is time for you now!
        Browse our numerous product and service to help your journal elevate to the next level including theme, plugin
        and indexing tools.
      </p>
      <p>
        OpenJournalTheme is a full-time team that is focused to provide dedicated experts in OJS products and services,
        which is consisted of experiences with more than ten years in web development industries.
        To realize transparency and openness to our clients, in every service we that we have provided we always include
        detailed reports that we share with you. So you'll get the best results with careful scrutiny by both parties.
        We put our expertise to serve our partners to provide the highest quality service possible. Our team also is
        actively participating in improving the quality of OJS software by collaborating in PKP and Github forums.
        The objectives of our clients are always our first priority. We aim to help any publisher manage any problem for
        a long-term relationship. we always provide suggestions for improving our partner journal sites.
      </p>
    </div>
  </article>

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


{include file="{$templatePath}:sitemap/footer.tpl"}