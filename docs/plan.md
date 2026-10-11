# Plan: shared helpers for CiviCRM extensions

Status: in progress. Open: the dependency check (step 2) and steps 4 and 5.

## Goal

Several CiviCRM extensions carry their own copy of the same small helpers, for
example a `Cast` class that coerces loosely typed values. This library holds one
maintained version of those helpers. Each extension bundles its own copy under its
own namespace, so two extensions on one site can run different library versions.

## Why every extension gets a prefixed copy

PHP has one class table per process. If two extensions bring the same
unprefixed class, the first copy loaded wins for everybody. Composer registers
its autoloaders with `prepend`, so this is the autoloader registered last.
A test with two extensions requiring incompatible majors of one library showed
both outcomes, depending on load order:

- a fatal `ArgumentCountError`,
- a silently wrong result.

[Strauss](https://github.com/BrianHenryIE/strauss) rewrites the library's
namespace into the extension's own, e.g. `Civico\Stdlib\Cast` becomes
`Civi\Example\Vendor\Civico\Stdlib\Cast`. Each copy is then a distinct class. The
same test with prefixed copies ran each extension against its own major in one
PHP process.

## Rules for the library

- PHP classes only, stateless: no hooks, no listeners, no global functions, no
  `files` autoload.
- Its types never cross an extension boundary. Each extension's copy is a
  different class, so extensions exchange APIv4 arrays and hook payloads only.
- No classes CiviCRM core resolves by name: QuickForm page callbacks, API
  entities, hook subscribers. Core loads those through the extension's own
  classloader. A QuickForm page callback is even loaded through the
  `include_path`.
- No dependencies beyond PHP. If one is ever needed, it must be a package core
  ships (e.g. `psr/log`). The library requires a range core satisfies, and the
  extension declares the package under `provide`.
- PHP 8.2 or newer.
- Semantic versioning. Before 1.0 a breaking change raises the minor version
  (0.1 → 0.2), since Composer reads `^0.1` as `>=0.1.0 <0.2.0`; from 1.0 on it
  needs a new major.

## First content: `Cast` (0.1.0)

These helpers coerce values from CiviRules storage, settings, JSON and DB
results, whose PHP type is not guaranteed.

| Method | Accepts | Anything else |
|---|---|---|
| `toString(mixed, string $default = '')` | scalars except NaN, cast as PHP does (`true` → `'1'`) | `$default` |
| `toInt(mixed, int $default = 0)` | ints, bools, numeric strings and floats within the int range (truncated) | `$default` |
| `toFloat(mixed, float $default = 0.0)` | ints, floats, numeric strings, bools | `$default` |
| `toStoredString(bool\|float\|int\|string)` | the value for a DB column (`false` → `'0'`) | NaN throws `ValueError` |
| `namedOnly(array)` | keeps the string keys | integer keys dropped, also `'5'` |

The existing copies disagree on edge cases:
- `toInt('12abc')` returns 12 in one extension and the default in others.
- Some copies turn bools into the default, others into 1/0.

The table above is the reference. An extension that moves to the library runs
its own test suite against it before switching.

Nullable variants (`toStringOrNull`, `toIntOrNull`) and `toBool` come in a
later release, together with the first extension that needs them. The existing
copies also disagree there: one copy treats `''` as absent, another does not.

## How an extension consumes the library

`composer.json`:

```json
{
  "require": { "civico-de/stdlib": "^0.1" },
  "provide": { "guzzlehttp/guzzle": "^7.0", "psr/log": "^3.0" },
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

- Strauss rewrites `vendor/` in place, so tools such as phpstan and IDEs see an
  ordinary Composer project. No extra configuration is needed.
- `composer.json` and `composer.lock` are committed, `vendor/` is not.
- The extension's main file loads `vendor/autoload.php`.
- Packages core ships (Guzzle, `psr/log`, `psr/http-message`, …) go under
  `provide`, never under `require`. Use the range that covers the core versions
  the extension supports, never `*`, which would hide an incompatibility.
  Composer quietly installs its own copy of a package when the `provide` range
  does not satisfy a requirement. That copy would then shadow core's copy for
  every extension.

## Build paths

Every path runs the same `composer install`. Strauss runs as its script.

| Where | Command | What ends up in `vendor/` |
|---|---|---|
| Development, CI | `composer install` | prefixed library, dev packages |
| Release archive | `composer install --no-dev`, `vendor/` added to the archive | prefixed library |
| Deploy from a git tag | `composer install --no-dev` in the staging directory | prefixed library |

- Strauss also writes `vendor/composer/autoload_aliases.php` and, with dev
  packages, wires it into `vendor/autoload.php`. The `composer dump-autoload`
  after it rewrites that file, so the unprefixed names never load in any build.
- Anything a build writes into the extension directory ships. The Strauss phar
  (about 11 MB) must therefore never land there.

## Strauss runner

The extension scaffold (civikitchen) ships `.civikitchen/strauss.php` as a
managed file, but only to repos whose `composer.json` declares `extra.strauss`.
The runner:

- pins one Strauss release and its SHA-256,
- caches the phar in the system temp directory,
- aborts on a checksum mismatch,
- passes its arguments through.

A Strauss upgrade is then a scaffold release plus `ckinit --update`.

## Dependency check

`ckdeps` fails when an extension's `composer.lock` installs a package that the
CiviCRM core it runs against also ships, dev packages included. Such a package
shadows core's copy and changes the code every other extension runs.

## Rollout

1. This library, 0.1.0 with `Cast` as specified above.
2. Scaffold: the Strauss runner and the dependency check.
3. First extension: replace its own `Cast`, add `provide` for the core packages
   it requires today, set `composer_install` in its CI and release callers, and
   switch its deploy to the Composer build.
4. Further extensions with their own `Cast` copy, one at a time, each with a
   new release here when it needs a new method.
5. Extensions that load an unprefixed `vendor/autoload.php` today. Strauss also
   prefixes their third-party packages, which removes the same shadowing risk.

## To verify during step 3

- phpstan reports an unprefixed `Civico\Stdlib\…` reference as an unknown class.
- The extension's full test suite passes against the reference semantics above.
