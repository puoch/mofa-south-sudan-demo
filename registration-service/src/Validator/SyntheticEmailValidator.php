<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SyntheticEmailValidator extends ConstraintValidator
{
    private const RESERVED_DOMAINS = ['example.com', 'example.org', 'example.net'];
    private const RESERVED_TLDS = ['example', 'test'];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SyntheticEmail) {
            throw new UnexpectedTypeException($constraint, SyntheticEmail::class);
        }
        if ($value === null || $value === '') {
            return;
        }
        $domain = strtolower(substr(strrchr((string) $value, '@') ?: '', 1));
        if (!self::isReserved($domain)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    public static function isReserved(string $domain): bool
    {
        foreach (self::RESERVED_DOMAINS as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.'.$reserved)) {
                return true;
            }
        }
        $labels = explode('.', $domain);

        return count($labels) > 1 && in_array(end($labels), self::RESERVED_TLDS, true);
    }
}
