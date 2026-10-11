<?php

declare(strict_types = 1);

namespace Civico\Stdlib\Tests;

use Civico\Stdlib\Cast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CastTest extends TestCase {

  /**
   * @return iterable<string, array{mixed, string}>
   */
  public static function strings(): iterable {
    yield 'string' => ['abc', 'abc'];
    yield 'int' => [42, '42'];
    yield 'float' => [1.5, '1.5'];
    yield 'true' => [TRUE, '1'];
    yield 'false' => [FALSE, ''];
    yield 'null' => [NULL, 'fallback'];
    yield 'array' => [['a'], 'fallback'];
    yield 'object' => [new \stdClass(), 'fallback'];
    yield 'NaN' => [NAN, 'fallback'];
    yield 'infinity' => [INF, 'INF'];
  }

  #[DataProvider('strings')]
  public function testToString(mixed $value, string $expected): void {
    self::assertSame($expected, Cast::toString($value, 'fallback'));
  }

  public function testToStringDefaultsToEmpty(): void {
    self::assertSame('', Cast::toString(NULL));
  }

  /**
   * @return iterable<string, array{bool|float|int|string, string}>
   */
  public static function storedStrings(): iterable {
    yield 'true' => [TRUE, '1'];
    yield 'false' => [FALSE, '0'];
    yield 'int' => [0, '0'];
    yield 'float' => [2.5, '2.5'];
    yield 'string' => ['x', 'x'];
    yield 'empty string' => ['', ''];
  }

  #[DataProvider('storedStrings')]
  public function testToStoredString(bool|float|int|string $value, string $expected): void {
    self::assertSame($expected, Cast::toStoredString($value));
  }

  public function testToStoredStringRejectsNan(): void {
    $this->expectException(\ValueError::class);
    Cast::toStoredString(NAN);
  }

  /**
   * @return iterable<string, array{mixed, int}>
   */
  public static function ints(): iterable {
    yield 'int' => [12, 12];
    yield 'negative int' => [-3, -3];
    yield 'numeric string' => ['12', 12];
    yield 'padded numeric string' => [' 12 ', 12];
    yield 'decimal string' => ['12.9', 12];
    yield 'exponent string' => ['1e3', 1000];
    yield 'float' => [12.9, 12];
    yield 'negative float' => [-12.9, -12];
    yield 'true' => [TRUE, 1];
    yield 'false' => [FALSE, 0];
    yield 'smallest int as float' => [(float) PHP_INT_MIN, PHP_INT_MIN];
    yield 'non-numeric string' => ['abc', 7];
    yield 'leading digits' => ['12abc', 7];
    yield 'empty string' => ['', 7];
    yield 'null' => [NULL, 7];
    yield 'array' => [[1], 7];
    yield 'NaN' => [NAN, 7];
    yield 'infinity' => [INF, 7];
    yield 'float beyond int range' => [1e19, 7];
    yield 'string beyond int range' => ['1e100', 7];
    yield '2^63' => [-(float) PHP_INT_MIN, 7];
  }

  #[DataProvider('ints')]
  public function testToInt(mixed $value, int $expected): void {
    self::assertSame($expected, Cast::toInt($value, 7));
  }

  public function testToIntDefaultsToZero(): void {
    self::assertSame(0, Cast::toInt('abc'));
  }

  /**
   * @return iterable<string, array{mixed, float}>
   */
  public static function floats(): iterable {
    yield 'float' => [1.5, 1.5];
    yield 'int' => [2, 2.0];
    yield 'numeric string' => ['2.25', 2.25];
    yield 'true' => [TRUE, 1.0];
    yield 'false' => [FALSE, 0.0];
    yield 'non-numeric string' => ['abc', 9.5];
    yield 'null' => [NULL, 9.5];
    yield 'array' => [[1.5], 9.5];
  }

  #[DataProvider('floats')]
  public function testToFloat(mixed $value, float $expected): void {
    self::assertSame($expected, Cast::toFloat($value, 9.5));
  }

  public function testToFloatDefaultsToZero(): void {
    self::assertSame(0.0, Cast::toFloat('abc'));
  }

  public function testNamedOnlyDropsIntegerKeys(): void {
    self::assertSame(['a' => 1, 'c' => [3]], Cast::namedOnly(['a' => 1, 0 => 2, 'c' => [3], 5 => 'x']));
  }

  public function testNamedOnlyDropsNumericStringKeys(): void {
    self::assertSame(['a' => 1, '05' => 'y'], Cast::namedOnly(['5' => 'x', 'a' => 1, '05' => 'y']));
  }

  public function testNamedOnlyOfAListIsEmpty(): void {
    self::assertSame([], Cast::namedOnly(['x', 'y']));
  }

}
