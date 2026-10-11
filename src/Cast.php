<?php

declare(strict_types = 1);

namespace Civico\Stdlib;

/**
 * Coercers for values whose PHP type is not guaranteed: settings, JSON, DB
 * results, rule configuration. The to* methods with a default return it for
 * anything they cannot convert.
 */
final class Cast {

  /**
   * Scalars as PHP casts them (TRUE becomes '1', FALSE ''); arrays, objects and
   * NaN never become "Array" or "NAN".
   */
  public static function toString(mixed $value, string $default = ''): string {
    return is_scalar($value) && !self::isNan($value) ? (string) $value : $default;
  }

  /**
   * A scalar for a database column: booleans become '1'/'0', not '', other
   * scalars what PHP casts them to. NaN has no stored form.
   *
   * @throws \ValueError
   */
  public static function toStoredString(bool|float|int|string $value): string {
    if (self::isNan($value)) {
      throw new \ValueError('NaN has no stored form');
    }
    return match ($value) {
      TRUE => '1',
      FALSE => '0',
      default => (string) $value,
    };
  }

  /**
   * Ints, bools, numeric strings and floats within the integer range, the
   * latter truncated.
   */
  public static function toInt(mixed $value, int $default = 0): int {
    $number = self::toNumber($value);
    if (is_float($number)) {
      // NaN fails both comparisons; 2^63 itself is already out of range.
      return $number >= (float) PHP_INT_MIN && $number < -(float) PHP_INT_MIN ? (int) $number : $default;
    }
    return $number ?? $default;
  }

  /**
   * Ints, floats, bools and numeric strings.
   */
  public static function toFloat(mixed $value, float $default = 0.0): float {
    $number = self::toNumber($value);
    return $number === NULL ? $default : (float) $number;
  }

  /**
   * Drops integer keys: a decoded JSON list has them, and they are neither
   * variable names nor field names. PHP stores a key like '5' as an integer.
   *
   * @param array<array-key, mixed> $values
   * @return array<string, mixed>
   */
  public static function namedOnly(array $values): array {
    $named = [];
    foreach ($values as $key => $value) {
      if (is_string($key)) {
        $named[$key] = $value;
      }
    }
    return $named;
  }

  private static function isNan(mixed $value): bool {
    return is_float($value) && is_nan($value);
  }

  private static function toNumber(mixed $value): int|float|null {
    return match (TRUE) {
      is_int($value), is_float($value) => $value,
      is_bool($value) => (int) $value,
      is_string($value) && is_numeric($value) => $value + 0,
      default => NULL,
    };
  }

}
