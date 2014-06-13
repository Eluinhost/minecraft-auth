<?php
namespace PublicUHC\MinecraftAuth\ReactServer\DataTypes;

class VarIntTest extends \PHPUnit_Framework_TestCase {

    public function testVarIntParseSingle()
    {
        //decoded = 15
        //dec = 15
        //hex = 0x0F
        //bin = 0000 1111
        $data = hex2bin('0F');

        echo "DATA: ".ord($data);

        $decoded = VarInt::readUnsignedVarInt($data);

        $this->assertEquals(15, $decoded->getValue());
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
    }

    public function testVarIntGenerateSingle()
    {
        $encoded = VarInt::writeUnsignedVarInt(1);
        $this->assertEquals(1, $encoded->getValue());
    }

    public function testVarIntGenerateMulti()
    {
        $encoded = VarInt::writeUnsignedVarInt(300);
        $this->assertEquals(hex2bin('AC02'), $encoded->getEncoded());
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
        //bin - 1000 0000 1000 0000 1000 0000 1000 0000 1000 0000 1000 0000
        //hex - 0x808080808080
        $data = hex2bin('808080808080');

        $this->setExpectedException('\PublicUHC\MinecraftAuth\ReactServer\InvalidDataException');
        VarInt::readUnsignedVarInt($data);
    }
} 