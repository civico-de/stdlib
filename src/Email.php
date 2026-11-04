<?php

declare(strict_types = 1);

namespace Civico\Stdlib;

/**
 * E-mail addresses as keys, so two notations of one address compare equal.
 */
final class Email {

  /**
   * Trimmed and ASCII-lowercased, '' for anything Cast::toString makes ''.
   * The local part is lowercased too; the form is for matching, not sending.
   */
  public static function key(mixed $value): string {
    return strtolower(trim(Cast::toString($value)));
  }

}
