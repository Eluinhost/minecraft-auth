<?php
namespace PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders;

class VarIntTest extends \PHPUnit_Framework_TestCase {

    public function testVarIntParseSingle()
    {
        //decoded = 15
        //dec = 15
        //hex = 0x0F
        //bin = 0000 1111
        $data = hex2bin('0F');
        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertEquals(15, $decoded->getValue());
        $this->assertEquals(hex2bin('0F'), $decoded->getEncoded());
        $this->assertEquals(1, $decoded->getDataLength());

        //decoded = 0
        //dec = 0
        //hex = 0x00
        //bin = 0000 0000
        $data = hex2bin('00');
        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertEquals(0, $decoded->getValue());
        $this->assertEquals(hex2bin('00'), $decoded->getEncoded());
        $this->assertEquals(1, $decoded->getDataLength());
    }

    public function testVarIntParseMulti()
    {
        //decoded = 300
        //dec = 44034
        //hex = 0xAC02
        //bin = 1010 1100 0000 0010
        $data = hex2bin('AC02');

        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertEquals(300, $decoded->getValue());
        $this->assertEquals($data, $decoded->getEncoded());
        $this->assertEquals(2, $decoded->getDataLength());

        //decoded = 9238
        //dec = 38472
        //hex = 0x9648
        //bin = 1001 0110 0100 1000
        $data = hex2bin('9648');
        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertEquals(9238, $decoded->getValue());
        $this->assertEquals($data, $decoded->getEncoded());
        $this->assertEquals(2, $decoded->getDataLength());
    }

    public function testVarIntGenerateSingle()
    {
        $encoded = VarInt::writeUnsignedVarInt(0);
        $this->assertEquals(0, $encoded->getValue());
        $this->assertEquals(hex2bin('00'), $encoded->getEncoded());
        $this->assertEquals(1, $encoded->getDataLength());

        $encoded = VarInt::writeUnsignedVarInt(15);
        $this->assertEquals(15, $encoded->getValue());
        $this->assertEquals(hex2bin('0F'), $encoded->getEncoded());
        $this->assertEquals(1, $encoded->getDataLength());
    }

    public function testVarIntGenerateMulti()
    {
        $encoded = VarInt::writeUnsignedVarInt(300);
        $this->assertEquals(300, $encoded->getValue());
        $this->assertEquals(hex2bin('AC02'), $encoded->getEncoded());
        $this->assertEquals(2, $encoded->getDataLength());

        $encoded = VarInt::writeUnsignedVarInt(9238);
        $this->assertEquals(9238, $encoded->getValue());
        $this->assertEquals(hex2bin('9648'), $encoded->getEncoded());
        $this->assertEquals(2, $encoded->getDataLength());
    }

    public function testVarIntParsePartialSingle()
    {
        //partial - 1000 0000, MSB is 1 therefore needs at least another byte
        //hex - 0x80
        $data = hex2bin('80');

        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertFalse($decoded);
    }

    public function testVarIntParsePartialMulti()
    {
        //partial - 1001 0110 1001 0100 1000 0000, MSB of each is 1 therefore needs at least another byte
        //hex - 0x969480
        $data = hex2bin('969480');

        $decoded = VarInt::readUnsignedVarInt($data);
        $this->assertFalse($decoded);
    }

    public function testVarIntTooLarge()
    {
        //more than 5 bytes = failure
        //bin - 1000 0000 1000 0000 1000 0000 1000 0000 1000 0000 1000 0000
        //hex - 0x808080808080
        $data = hex2bin('808080808080');

        $this->setExpectedException('\PublicUHC\MinecraftAuth\ReactServer\InvalidDataException');
        VarInt::readUnsignedVarInt($data);
    }
}
