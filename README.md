# Contao SEAL

> [!CAUTION]
> Work in progress

This project integrates [SEAL](https://github.com/php-cmsig/search) with the [Contao Open Source CMS](https://www.contao.org).

It lets you add as many search indexes as you like for the frontend. Every index needs a `search adapter` and a 
`provider`:

* The `search adapter` is the SEAL adapter you want to use. By default, this extension ships with native support for 
  [Loupe](https://github.com/loupe-php/loupe) which you can use without any dependencies other than `SQLite`. 
  Search adapters can be added using configuration, see next section.
* The `provider` is responsible for indexing, searching and formatting the output. For a search engine, indexing and 
  searching are inseparable. You need to know what you need to index depending on what you want to search for or 
  output in your search results. Hence, there are providers which are provided either directly by this extension or 
  any third-party logic. This extension ships with the `Standard` provider which provides the logic that you are 
  probably familiar with from the Contao Core engine: A search result consists of page URLs with some context and 
  optionally an image. For querying, it only supports a keyword search field. That's it. However, you could imagine 
  a provider for e.g. Isotope eCommerce which does not only provide a search field but also some price range filter 
  and lists products instead of page URLs.

## Configuration

This extension does not require any configuration. However, if you want to use a different search engine for example,
you need to add this in your `config.yaml` (or `config.php`) first:

```yaml
terminal42_contao_seal:
    adapters:
        'loupe_default': 'loupe://%kernel.project_dir%/var/loupe' # Loupe, storing the indexes in `var/loupe`
        'meilisearch_server_a': 'meilisearch://127.0.0.1:7700'
        'meilisearch_server_b': 'meilisearch://127.0.0.1:7701'
```

The notation for the search adapters can be found in the [SEAL Symfony Bundle](https://github.com/PHP-CMSIG/seal-symfony-bundle)

## What are the advantages compared to the Contao Core search engine?

* This extension integrates with SEAL which means you can use any supported search engine (ElasticSearch, MeiliSearch, Redis, Loupe etc.)
  so you can benefit from the individual strengths of those engines such as typo tolerance, which the Contao Core engine
  does not support.
* Supports indexing protected contents (content elements, articles, modules) as of [Contao 5.6](https://github.com/contao/contao/pull/8395).
  The Contao Core engine skips those elements because it cannot make a difference between different member group permissions.
  This extension, however, can.
* The Contao Core engine only supports one global search index which limits the possibilities. For example, this extension
  can ignore documents with a certain `rel="canonical"` filter for one index while indexing them for another one.
* Likely faster because the search engines are faster. 🚀