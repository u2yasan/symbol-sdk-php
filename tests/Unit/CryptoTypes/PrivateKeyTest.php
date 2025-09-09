<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\CryptoTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Enums\NetworkType;

final class PrivateKeyTest extends TestCase
{
    private const SAMPLE_PRIVATE_KEY = '5DB8324E7EB83E7665D500B014283260EF312139034E86DFB7EE736503EA2222';

    #[Test]
    public function creates_from_hex_string(): void
    {
        $privateKey = new PrivateKey(self::SAMPLE_PRIVATE_KEY);
        self::assertEquals(self::SAMPLE_PRIVATE_KEY, $privateKey->toString());
    }

    #[Test]
    public function creates_from_hex_with_prefix(): void
    {
        $privateKey = new PrivateKey('0x' . self::SAMPLE_PRIVATE_KEY);
        self::assertEquals(self::SAMPLE_PRIVATE_KEY, $privateKey->toString());
    }

    #[Test]
    public function creates_random_key(): void
    {
        $privateKey1 = PrivateKey::random();
        $privateKey2 = PrivateKey::random();
        
        self::assertEquals(64, strlen($privateKey1->toString()));
        self::assertEquals(64, strlen($privateKey2->toString()));
        self::assertNotEquals($privateKey1->toString(), $privateKey2->toString());
    }

    #[Test]
    public function creates_from_mnemonic(): void
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $privateKey = PrivateKey::fromMnemonic($mnemonic);
        
        self::assertEquals(64, strlen($privateKey->toString()));
        self::assertTrue(ctype_xdigit($privateKey->toString()));
    }

    #[Test]
    public function derives_public_key(): void
    {
        $privateKey = new PrivateKey(self::SAMPLE_PRIVATE_KEY);
        $publicKey = $privateKey->derivePublicKey();
        
        self::assertEquals(64, strlen($publicKey->toString()));
        self::assertTrue(ctype_xdigit($publicKey->toString()));
    }

    #[Test]
    public function creates_account(): void
    {
        $privateKey = new PrivateKey(self::SAMPLE_PRIVATE_KEY);
        $account = $privateKey->createAccount(NetworkType::TESTNET);
        
        self::assertEquals(NetworkType::TESTNET, $account->networkType);
        self::assertTrue($account->privateKey->equals($privateKey));
    }

    #[Test]
    public function signs_data(): void
    {
        $privateKey = new PrivateKey(self::SAMPLE_PRIVATE_KEY);
        $data = 'Hello, Symbol!';
        
        $signature = $privateKey->sign($data);
        
        self::assertEquals(128, strlen($signature->toString()));
        self::assertTrue(ctype_xdigit($signature->toString()));
    }

    #[Test]
    public function throws_on_invalid_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PrivateKey('12345'); // Too short
    }

    #[Test]
    public function throws_on_zero_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PrivateKey(str_repeat('0', 64));
    }

    #[Test]
    public function throws_on_non_hex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PrivateKey('ZZZZ324E7EB83E7665D500B014283260EF312139034E86DFB7EE736503EA2222');
    }
}
