<?php
namespace PublicUHC\MinecraftAuth\AuthServer;

use Crypt_RSA;

class Certificate {

    private $publicKey;
    private $privateKey;

    public function __construct()
    {
        $this->privateKey = new Crypt_RSA();

        $keys = $this->privateKey->createKey();
        $privKey = $keys['privatekey'];
        $this->privateKey->loadKey($privKey);
        $this->privateKey->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);

        $pubKey = $keys['publickey'];
        $this->publicKey = new Crypt_RSA();
        $this->publicKey->loadKey($pubKey);
        $this->publicKey->setPublicKey();
        $this->publicKey->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
    }

    /**
     * @return Crypt_RSA the public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return Crypt_RSA the private key
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }
} 