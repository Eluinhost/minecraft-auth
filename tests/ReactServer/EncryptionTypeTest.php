<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Crypt_Rijndael;
use PHPUnit_Framework_TestCase;

class EncryptionTypeTest extends PHPUnit_Framework_TestCase {

    public function testEqualEncryption()
    {
        $secret = hex2bin('0742470acdcc507bc3377e35093c519a');

        $randomdata = hex2bin('a001009d017b226578747261223a5b224e6f742061757468656e74696361746564207769746820222c7b22756e6465726c696e6564223a66616c73652c22636c69636b4576656e74223a7b22616374696f6e223a226f70656e5f75726c222c2276616c7565223a22687474703a2f2f4d696e6563726166742e6e6574227d2c2274657874223a224d696e6563726166742e6e6574227d5d2c2274657874223a22227d');

        $rij = new Crypt_Rijndael(CRYPT_RIJNDAEL_MODE_CFB);
        $rij->setBlockLength(128);
        $rij->setKeyLength(128);
        $rij->setIV($secret);
        $rij->setKey($secret);

        $seclib = $rij->encrypt($randomdata);

        $mcrypt = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            $secret,
            $randomdata,
            MCRYPT_MODE_CFB,
            $secret
        );

        $this->assertEquals($seclib, $mcrypt);
    }
} 