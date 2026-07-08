# UniRate Bundle — Symfony Integration

Symfony bundle that integrates the [UniRate API](https://unirateapi.com) as an autowired service for currency exchange rates, conversions, and VAT data.

## Install

```bash
composer require unirate-api/unirate-bundle
```

Register the bundle in `config/bundles.php`:

```php
UniRateApi\Bundle\UniRateBundle::class => ['all' => true],
```

## Configuration

Create `config/packages/unirate.yaml`:

```yaml
unirate:
    api_key: '%env(UNIRATE_API_KEY)%'
    # base_url: 'https://api.unirateapi.com'  # default
    # timeout: 30                              # seconds, default
```

## Usage

The `UniRateClient` service is autowired automatically:

```php
use UniRateApi\Bundle\UniRateClient;

class CurrencyController
{
    public function __construct(private readonly UniRateClient $unirate) {}

    public function rate(): Response
    {
        // Single rate
        $rate = $this->unirate->getRate('USD', 'EUR');       // → 0.9234

        // All rates for a base
        $rates = $this->unirate->getRates('USD');            // → ['EUR' => 0.92, ...]

        // Convert amount
        $result = $this->unirate->convert(100.0, 'USD', 'EUR'); // → 92.34

        // Supported currencies
        $currencies = $this->unirate->listCurrencies();      // → ['USD', 'EUR', ...]

        // VAT rates
        $allVat = $this->unirate->getVatRates();             // all countries
        $deVat  = $this->unirate->getVatRates('DE');         // Germany only
    }
}
```

## Error handling

```php
use UniRateApi\Bundle\UniRateException;

try {
    $rate = $this->unirate->getRate('USD', 'EUR');
} catch (UniRateException $e) {
    $httpStatus = $e->getStatusCode(); // 401, 403, 404, 429, etc.
    $message    = $e->getMessage();
}
```

HTTP status codes: 401 = invalid key · 403 = Pro required · 404 = currency not found · 429 = rate limit exceeded.

## Free vs Pro tier

Free tier: rates, convert, currencies, VAT. Historical data requires [Pro](https://unirateapi.com/pricing).

## Packagist

Distribution via Packagist. Registration is required once at packagist.org — point it at this GitHub repo and GitHub webhooks handle all subsequent updates automatically.

## Related packages

<!-- unirate-ecosystem-start -->
**UniRate API client libraries:** [Python](https://github.com/UniRate-API/unirate-api-python) · [Node.js](https://github.com/UniRate-API/unirate-api-nodejs) · [Go](https://github.com/UniRate-API/unirate-api-go) · [Rust](https://github.com/UniRate-API/unirate-api-rust) · [Ruby](https://github.com/UniRate-API/unirate-api-ruby) · [PHP](https://github.com/UniRate-API/unirate-api-php) · [Java](https://github.com/UniRate-API/unirate-api-java) · [Swift](https://github.com/UniRate-API/unirate-api-swift) · [.NET](https://github.com/UniRate-API/unirate-api-dotnet)

**PHP ecosystem:** [Laravel Money](https://github.com/UniRate-API/laravel-money-unirate) · [WordPress](https://github.com/UniRate-API/unirate-currency-converter) · **Symfony Bundle** (this package)

**Framework integrations:** [Next.js](https://github.com/UniRate-API/next-unirate) · [Nuxt](https://github.com/UniRate-API/nuxt-unirate) · [SvelteKit](https://github.com/UniRate-API/sveltekit-unirate) · [Astro](https://github.com/UniRate-API/astro-unirate) · [NestJS](https://github.com/UniRate-API/nestjs-unirate) · [Strapi](https://github.com/UniRate-API/strapi-plugin-unirate)

**Data & AI:** [LangChain Python](https://github.com/UniRate-API/langchain-unirate) · [FastAPI](https://github.com/UniRate-API/fastapi-unirate) · [Flask](https://github.com/UniRate-API/flask-unirate) · [Django REST](https://github.com/UniRate-API/djangorestframework-unirate) · [dbt](https://github.com/UniRate-API/dbt-unirate) · [Airflow](https://github.com/UniRate-API/airflow-provider-unirate)

**Other:** [MCP server](https://github.com/UniRate-API/unirate-mcp) · [CLI](https://github.com/UniRate-API/unirate-cli) · [Directus](https://github.com/UniRate-API/directus-extension-unirate) · [Medusa](https://github.com/UniRate-API/medusa-plugin-unirate) · [Home Assistant](https://github.com/UniRate-API/unirate-home-assistant)
<!-- unirate-ecosystem-end -->

<!-- unirate-ecosystem-footer:start -->
## UniRate ecosystem

UniRate ships official integrations for 40+ ecosystems, all maintained under the
[UniRate-API](https://github.com/UniRate-API) org.

**Core clients (9 languages)**
[Python](https://github.com/UniRate-API/unirate-api-python) ·
[Node.js / TypeScript](https://github.com/UniRate-API/unirate-api-nodejs) ·
[Go](https://github.com/UniRate-API/unirate-api-go) ·
[Rust](https://github.com/UniRate-API/unirate-api-rust) ·
[Java](https://github.com/UniRate-API/unirate-api-java) ·
[Ruby](https://github.com/UniRate-API/unirate-api-ruby) ·
[PHP](https://github.com/UniRate-API/unirate-api-php) ·
[.NET](https://github.com/UniRate-API/unirate-api-dotnet) ·
[Swift](https://github.com/UniRate-API/unirate-api-swift)

**JavaScript / TypeScript**
[React](https://github.com/UniRate-API/react-unirate) ·
[Next.js](https://github.com/UniRate-API/next-unirate) ·
[Remix](https://github.com/UniRate-API/remix-unirate) ·
[SvelteKit](https://github.com/UniRate-API/sveltekit-unirate) ·
[Vue](https://github.com/UniRate-API/vue-unirate) ·
[Angular](https://github.com/UniRate-API/angular-unirate) ·
[Nuxt](https://github.com/UniRate-API/nuxt-unirate) ·
[NestJS](https://github.com/UniRate-API/nestjs-unirate) ·
[tRPC](https://github.com/UniRate-API/trpc-unirate)

**Static-site generators**
[Astro](https://github.com/UniRate-API/astro-unirate) ·
[Eleventy](https://github.com/UniRate-API/eleventy-unirate) ·
[Hugo](https://github.com/UniRate-API/hugo-unirate) ·
[Jekyll](https://github.com/UniRate-API/jekyll-unirate)

**CMS & e-commerce**
[Wagtail](https://github.com/UniRate-API/wagtail-unirate) ·
[WordPress](https://github.com/UniRate-API/unirate-currency-converter) ·
[WooCommerce](https://github.com/UniRate-API/unirate-woocs) ·
[Drupal](https://github.com/UniRate-API/drupal-unirate) ·
[Strapi](https://github.com/UniRate-API/strapi-plugin-unirate) ·
[Medusa](https://github.com/UniRate-API/medusa-plugin-unirate) ·
[Symfony](https://github.com/UniRate-API/unirate-bundle) ·
[Laravel](https://github.com/UniRate-API/laravel-money-unirate) ·
[Directus](https://github.com/UniRate-API/directus-extension-unirate)

**Data, AI & backend**
[LangChain (Python)](https://github.com/UniRate-API/langchain-unirate) ·
[LangChain.js](https://github.com/UniRate-API/langchain-js-unirate) ·
[FastAPI](https://github.com/UniRate-API/fastapi-unirate) ·
[Flask](https://github.com/UniRate-API/flask-unirate) ·
[Django REST Framework](https://github.com/UniRate-API/djangorestframework-unirate) ·
[Apache Airflow](https://github.com/UniRate-API/airflow-provider-unirate) ·
[dbt](https://github.com/UniRate-API/dbt-unirate)

**Platform & tools**
[MCP server](https://github.com/UniRate-API/unirate-mcp) ·
[CLI](https://github.com/UniRate-API/unirate-cli) ·
[Cloudflare Workers](https://github.com/UniRate-API/cloudflare-workers-unirate) ·
[Home Assistant](https://github.com/UniRate-API/unirate-home-assistant) ·
[n8n](https://github.com/UniRate-API/n8n-nodes-unirate) ·
[Google Sheets](https://github.com/UniRate-API/unirate-sheets) ·
[VS Code](https://github.com/UniRate-API/vscode-unirate) ·
[Obsidian](https://github.com/UniRate-API/obsidian-currency)

**Money library bridges**
[money gem (Ruby)](https://github.com/UniRate-API/money-unirate-api) ·
[NodaMoney (.NET)](https://github.com/UniRate-API/UniRateApi.NodaMoney)

Get a free API key at [unirateapi.com](https://unirateapi.com).
<!-- unirate-ecosystem-footer:end -->

## License

MIT © Unirate Team