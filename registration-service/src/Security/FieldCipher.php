<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Encrypts sensitive registration fields at application level.
 *
 * Values are sealed with XChaCha20-Poly1305 (libsodium). The field name is
 * bound to the ciphertext as associated data, so a value cannot be moved to
 * another column without failing to decrypt. A separate key produces keyed
 * hashes (blind indexes) for duplicate checks, so the database never needs
 * the plain passport number to compare records.
 *
 * In this prototype the keys come from environment variables. Before any
 * real use they must move to a key management service (see ADR 0014).
 */
final class FieldCipher
{
    private const VERSION = 'v1';

    private readonly string $fieldKey;
    private readonly string $indexKey;

    public function __construct(string $fieldKey, string $indexKey)
    {
        $this->fieldKey = self::decodeKey($fieldKey, 'REGISTRY_FIELD_KEY');
        $this->indexKey = self::decodeKey($indexKey, 'REGISTRY_INDEX_KEY');
        if (hash_equals($this->fieldKey, $this->indexKey)) {
            throw new \InvalidArgumentException('REGISTRY_FIELD_KEY and REGISTRY_INDEX_KEY must be different keys.');
        }
    }

    public function encrypt(string $plaintext, string $field): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, self::VERSION.':'.$field, $nonce, $this->fieldKey);

        return self::VERSION.':'.sodium_bin2base64($nonce.$ciphertext, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public function decrypt(string $sealed, string $field): string
    {
        [$version, $payload] = explode(':', $sealed, 2) + [1 => ''];
        if ($version !== self::VERSION) {
            throw new \UnexpectedValueException('Unknown ciphertext version.');
        }
        $raw = sodium_base642bin($payload, SODIUM_BASE64_VARIANT_ORIGINAL);
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw, $nonceLength), $version.':'.$field, substr($raw, 0, $nonceLength), $this->fieldKey);
        if ($plaintext === false) {
            throw new \UnexpectedValueException('Ciphertext could not be decrypted for field '.$field.'.');
        }

        return $plaintext;
    }

    /**
     * Returns a keyed hash of a normalized value, as 64 hex characters.
     */
    public function blindIndex(string $value, string $field): string
    {
        return bin2hex(sodium_crypto_generichash($field.':'.$value, $this->indexKey, 32));
    }

    private static function decodeKey(string $encoded, string $name): string
    {
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \InvalidArgumentException($name.' must be a base64-encoded 32-byte key.');
        }

        return $key;
    }
}
