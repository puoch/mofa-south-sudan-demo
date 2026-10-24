<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Accepts only addresses on domains reserved for testing (RFC 2606 and
 * RFC 6761), so the prototype cannot store a real person's email address.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SyntheticEmail extends Constraint
{
    public string $message = 'This prototype accepts only made-up addresses ending in example.com, example.org, example.net, .example or .test.';
}
