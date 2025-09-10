<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol;

use SymbolSdk\CryptoTypes\PublicKey;
use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Models\PublicKey as ModelsPublicKey;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\ValueObjects\Address;

/**
 * Symbol public account.
 */
class SymbolPublicAccount
{
    protected SymbolFacade $_facade;
    public PublicKey|ModelsPublicKey $publicKey;
    public UnresolvedAddress $address;

    /**
     * Creates a Symbol public account.
     * @param SymbolFacade $facade Symbol facade.
     * @param PublicKey|ModelsPublicKey $publicKey Account public key.
     */
    public function __construct(SymbolFacade $facade, PublicKey|ModelsPublicKey $publicKey)
    {
        $this->_facade = $facade;
        
        // PublicKeyの統一化
        if ($publicKey instanceof PublicKey) {
            // CryptoTypes\PublicKeyの場合
            $this->publicKey = new ModelsPublicKey($publicKey->toBytes());
            $cryptoPublicKey = $publicKey; // 既にCryptoTypes\PublicKey
        } else {
            // Models\PublicKeyの場合
            $this->publicKey = $publicKey;
            // Models\PublicKeyからCryptoTypes\PublicKeyに変換
            $keyData = $publicKey->binaryData ?? $publicKey->serialize();
            $cryptoPublicKey = new PublicKey($keyData);
        }
        
        // SymbolFacadeを使用してPublicAccountを作成し、アドレスを取得
        $publicAccount = $this->_facade->createPublicAccount($cryptoPublicKey);
        $this->address = new UnresolvedAddress($publicAccount->address->toString());
    }
}