<?php

namespace Drupal\pevb\Security;

use Drupal\Core\Access\CsrfTokenGenerator;

/**
 * Helper to generate/validate CSRF link tokens for GET actions.
 */
class LinkToken {
  public function __construct(private CsrfTokenGenerator $csrf) {}

  public function get(string $value): string {
    return $this->csrf->get($value);
  }

  public function validate(string $value, string $token): bool {
    return $this->csrf->validate($token, $value);
  }
}
