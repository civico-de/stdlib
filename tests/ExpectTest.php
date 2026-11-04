<?php

declare(strict_types = 1);

namespace Civico\Stdlib\Tests;

use Civico\Stdlib\Expect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpectTest extends TestCase {

  /**
   * @return iterable<string, array{mixed, int}>
   */
  public static function ints(): iterable {
    yield 'int' => [42, 42];
    yield 'numeric string' => ['42', 42];
    yield 'negative string' => ['-7', -7];
  }

  #[DataProvider('ints')]
  public function testInt(mixed $value, int $expected): void {
    self::assertSame($expected, Expect::int($value));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notInts(): iterable {
    yield 'null' => [NULL];
    yield 'decimal string' => ['1.5'];
    yield 'float' => [1.0];
    yield 'bool' => [TRUE];
    yield 'empty string' => [''];
    yield 'word' => ['abc'];
    yield 'array' => [[1]];
  }

  #[DataProvider('notInts')]
  public function testIntThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::int($value);
  }

  /**
   * @return iterable<string, array{mixed, string}>
   */
  public static function strings(): iterable {
    yield 'string' => ['abc', 'abc'];
    yield 'empty' => ['', ''];
    yield 'int' => [42, '42'];
    yield 'float' => [1.5, '1.5'];
  }

  #[DataProvider('strings')]
  public function testStr(mixed $value, string $expected): void {
    self::assertSame($expected, Expect::str($value));
    self::assertSame($expected, Expect::optStr($value));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notStrings(): iterable {
    yield 'bool' => [TRUE];
    yield 'array' => [['a']];
    yield 'object' => [new \stdClass()];
    yield 'NaN' => [NAN];
    yield 'infinity' => [INF];
  }

  #[DataProvider('notStrings')]
  public function testStrThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::str($value);
  }

  public function testStrThrowsOnNull(): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::str(NULL);
  }

  public function testOptStrPassesNull(): void {
    self::assertNull(Expect::optStr(NULL));
  }

  /**
   * @return iterable<string, array{mixed, bool}>
   */
  public static function bools(): iterable {
    yield 'true' => [TRUE, TRUE];
    yield 'false' => [FALSE, FALSE];
    yield 'one' => [1, TRUE];
    yield 'zero' => [0, FALSE];
    yield 'string one' => ['1', TRUE];
    yield 'string zero' => ['0', FALSE];
    yield 'empty string' => ['', FALSE];
  }

  #[DataProvider('bools')]
  public function testBool(mixed $value, bool $expected): void {
    self::assertSame($expected, Expect::bool($value));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notBools(): iterable {
    yield 'null' => [NULL];
    yield 'two' => [2];
    yield 'word false' => ['false'];
    yield 'word true' => ['true'];
    yield 'float' => [1.0];
    yield 'array' => [[]];
  }

  #[DataProvider('notBools')]
  public function testBoolThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::bool($value);
  }

  /**
   * @return iterable<string, array{mixed, float|null}>
   */
  public static function floats(): iterable {
    yield 'null' => [NULL, NULL];
    yield 'float' => [1.5, 1.5];
    yield 'int' => [2, 2.0];
    yield 'numeric string' => ['1.5', 1.5];
  }

  #[DataProvider('floats')]
  public function testOptFloat(mixed $value, ?float $expected): void {
    self::assertSame($expected, Expect::optFloat($value));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notFloats(): iterable {
    yield 'word' => ['x'];
    yield 'bool' => [TRUE];
    yield 'NaN' => [NAN];
    yield 'infinity' => [INF];
    yield 'overflowing string' => ['1e999'];
  }

  #[DataProvider('notFloats')]
  public function testOptFloatThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::optFloat($value);
  }

  public function testRow(): void {
    self::assertSame(['id' => 1, 'name' => NULL], Expect::row(['id' => 1, 'name' => NULL]));
    self::assertSame([], Expect::row([]));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notRows(): iterable {
    yield 'null' => [NULL];
    yield 'string' => ['row'];
    yield 'list' => [[1, 2]];
    yield 'mixed keys' => [['id' => 1, 5 => 'x']];
  }

  #[DataProvider('notRows')]
  public function testRowThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::row($value);
  }

  public function testTimestamp(): void {
    self::assertSame(86400, Expect::timestamp('1970-01-02T00:00:00Z'));
    self::assertSame(0, Expect::timestamp('@0'));
  }

  /**
   * @return iterable<string, array{mixed}>
   */
  public static function notTimestamps(): iterable {
    yield 'null' => [NULL];
    yield 'empty string' => [''];
    yield 'garbage' => ['not a date'];
    yield 'array' => [[]];
  }

  #[DataProvider('notTimestamps')]
  public function testTimestampThrows(mixed $value): void {
    $this->expectException(\UnexpectedValueException::class);
    Expect::timestamp($value);
  }

  public function testMessageNamesTheTypeNotTheValue(): void {
    try {
      Expect::int('secret@example.org');
      self::fail('No exception');
    }
    catch (\UnexpectedValueException $e) {
      self::assertSame('Expected an integer, got string', $e->getMessage());
    }
  }

}
