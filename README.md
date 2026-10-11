# civico-de/stdlib

Small stateless helpers for CiviCRM extensions. Each extension bundles its own
copy under its own namespace, so two extensions on one site can depend on
different versions of this library.

## Why a prefixed copy

PHP has one class table per process. If two extensions ship the same
unprefixed class, the first copy loaded serves both. That can end in a fatal
error, or in a silently wrong result if the versions differ. An extension
therefore runs this library through
[Strauss](https://github.com/BrianHenryIE/strauss), which rewrites
`Civico\Stdlib\Cast` into the extension's namespace, for example
`Civi\Example\Vendor\Civico\Stdlib\Cast`.

## Using it in an extension

```json
{
  "require": {
    "civico-de/stdlib": "^0.1"
  },
  "provide": {
    "guzzlehttp/guzzle": "^7.0",
    "psr/log": "^3.0"
  },
  "extra": {
    "strauss": {
      "target_directory": "vendor",
      "namespace_prefix": "Civi\\Example\\Vendor\\",
      "classmap_prefix": "Civi_Example_Vendor_",
      "packages": ["civico-de/stdlib"]
    }
  },
  "scripts": {
    "prefix-namespaces": ["@php .civikitchen/strauss.php", "@composer dump-autoload"],
    "post-install-cmd": ["@prefix-namespaces"],
    "post-update-cmd": ["@prefix-namespaces"]
  }
}
```

- Strauss rewrites `vendor/` in place, so phpstan and IDEs see an ordinary
  Composer project.
- Commit `composer.json` and `composer.lock`. Keep `vendor/` out of git, and
  build it with `composer install --no-dev` for releases and deploys.
- Load `vendor/autoload.php` from the extension's main file.
- `.civikitchen/strauss.php` runs a pinned, checksum-verified Strauss release
  from the system temp directory. The [civikitchen](https://github.com/jfilter/civikitchen)
  scaffold provides it. Any build that keeps the phar outside the extension
  directory works too.

### Packages core ships

Packages that CiviCRM core already ships (Guzzle, `psr/log`,
`psr/http-message`, …) belong under `provide`, not `require`:
- A bundled copy would replace core's copy for every extension on the site.
- Use the range that covers the core versions the extension supports. Never
  use `*`: it hides incompatibilities.
- When the `provide` range does not satisfy a requirement, Composer installs its
  own copy without a warning. Check `composer.lock` after an update.

Strauss also writes `vendor/composer/autoload_aliases.php`, which would map the
unprefixed names to the extension's copy. The `composer dump-autoload` that
follows leaves it unreferenced, so the unprefixed names never load, in
development as in production.

## What is in it

| Class | Purpose |
|---|---|
| `Cast` | Coerces values of unknown type: settings, JSON, DB results, rule configuration. |

`toString`, `toInt` and `toFloat` return the given default for anything they cannot
convert. None of them turns an array into `"Array"`:

| Method | Converts | Otherwise |
|---|---|---|
| `toString($value, $default = '')` | scalars except NaN, as PHP casts them (`true` → `'1'`) | `$default` |
| `toInt($value, $default = 0)` | ints, bools, numeric strings and floats within the int range (truncated) | `$default` |
| `toFloat($value, $default = 0.0)` | ints, floats, bools, numeric strings | `$default` |
| `toStoredString($scalar)` | the value for a database column (`false` → `'0'`) | NaN throws `ValueError` |
| `namedOnly($array)` | keeps entries with string keys | — |

`toInt` range-checks floats, decimal strings and strings beyond the int range
as floats: within 1024 of `PHP_INT_MIN` or `PHP_INT_MAX` they can round across it.
`namedOnly` also drops keys like `'5'`, which PHP stores as integers.

## Rules for new helpers

- PHP classes only, stateless: no hooks, no listeners, no global functions.
- Types never cross an extension boundary. Each extension's copy is a distinct
  class, so extensions exchange arrays and hook payloads only.
- No classes CiviCRM core resolves by name, such as QuickForm page callbacks,
  API entities or hook subscribers.
- No dependencies beyond PHP, unless core ships the package.

## Development

```bash
composer update
composer analyse   # phpstan, level max with strict rules
composer test      # phpunit
```

CI runs both on the lowest and the highest supported PHP version.

## Releasing

Semantic versioning. Before 1.0 a breaking change raises the minor version
(0.1 → 0.2), since Composer reads `^0.1` as `>=0.1.0 <0.2.0`; anything else
raises the patch version. From 1.0 on, a breaking change needs a new major.

1. Add the version to `CHANGELOG.md` on `main`.
2. Tag it as `vX.Y.Z`.

Composer reads the version from the tag; no file carries it.

## License

AGPL-3.0-or-later, see [LICENSE](LICENSE).
