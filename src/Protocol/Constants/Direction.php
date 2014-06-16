<?php
namespace PublicUHC\MinecraftAuth\Protocol\Constants;

use MabeEnum\Enum;

/**
 * Class Direction
 * @package PublicUHC\MinecraftAuth\Protocol\Constants
 *
 * @method static Direction SEVERBOUND()
 * @method static Direction CLIENTBOUND()
 */
class Direction extends Enum {

    const SERVERBOUND = 0;
    const CLIENTBOUND = 1;

} 