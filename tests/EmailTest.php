<?php

declare(strict_types = 1);

namespace Civico\Stdlib\Tests;

use Civico\Stdlib\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase {

  /**
   * @return iterable<string, array{mixed, string}>
   */
  public static function keys(): iterable {
    yield 'plain' => ['a@example.org', 'a@example.org'];
    yield 'mixed case' => ['Anna.B@Example.ORG', 'anna.b@example.org'];
    yield 'padded' => ["  a@example.org\n", 'a@example.org'];
    yield 'non-ASCII kept' => ['Ä@example.org', 'Ä@example.org'];
    yield 'empty' => ['', ''];
    yield 'null' => [NULL, ''];
    yield 'array' => [['a@example.org'], ''];
  }

  #[DataProvider('keys')]
  public function testKey(mixed $value, string $expected): void {
    self::assertSame($expected, Email::key($value));
  }

}
