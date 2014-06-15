<?php
namespace PublicUHC\MinecraftAuth\ReactServer\Encryption;

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

        $pubKey = $keys['publickey'];
        $this->publicKey = new Crypt_RSA();
        $this->publicKey->loadKey($pubKey);
        $this->publicKey->setPublicKey();
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