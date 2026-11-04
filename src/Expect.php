<?php

declare(strict_types = 1);

namespace Civico\Stdlib;

/**
 * Readers for values that must have a given type, such as APIv4 rows, settings
 * and decoded JSON. Each throws UnexpectedValueException instead of returning a
 * default; messages name the type, never the value.
 */
final class Expect {

  /**
   * Ints and strings holding an integer ('42', '-7'); '1.5' and floats throw.
   */
  public static function int(mixed $value): int {
    if (is_int($value)) {
      return $value;
    }
    $int = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : FALSE;
    return is_int($int) ? $int : throw self::unexpected('an integer', $value);
  }

  /**
   * Strings, ints and finite floats.
   */
  public static function str(mixed $value): string {
    return match (TRUE) {
      is_string($value) => $value,
      is_int($value), is_float($value) && is_finite($value) => (string) $value,
      default => throw self::unexpected('a string', $value),
    };
  }

  public static function optStr(mixed $value): ?string {
    return $value === NULL ? NULL : self::str($value);
  }

  /**
   * Bools and the stored forms 1/0, '1'/'0' and ''; 'false' or 2 throw.
   */
  public static function bool(mixed $value): bool {
    return match ($value) {
      TRUE, 1, '1' => TRUE,
      FALSE, 0, '0', '' => FALSE,
      default => throw self::unexpected('a boolean', $value),
    };
  }

  /**
   * NULL, ints, finite floats and numeric strings.
   */
  public static function optFloat(mixed $value): ?float {
    if ($value === NULL) {
      return NULL;
    }
    $float = is_int($value) || is_float($value) || is_string($value) && is_numeric($value) ? (float) $value : NAN;
    return is_finite($float) ? $float : throw self::unexpected('a number', $value);
  }

  /**
   * An array with string keys only, such as an APIv4 row; [] passes.
   *
   * @return array<string, mixed>
   */
  public static function row(mixed $value): array {
    if (!is_array($value)) {
      throw self::unexpected('a row', $value);
    }
    $row = [];
    foreach ($value as $key => $field) {
      $row[is_string($key) ? $key : throw self::unexpected('a row', $value)] = $field;
    }
    return $row;
  }

  /**
   * A date string strtotime() understands, as a Unix timestamp.
   */
  public static function timestamp(mixed $value): int {
    $timestamp = strtotime(self::str($value));
    return $timestamp === FALSE ? throw new \UnexpectedValueException('Expected a date, got an unparsable string') : $timestamp;
  }

  private static function unexpected(string $expected, mixed $value): \UnexpectedValueException {
    return new \UnexpectedValueException("Expected {$expected}, got " . get_debug_type($value));
  }

}
