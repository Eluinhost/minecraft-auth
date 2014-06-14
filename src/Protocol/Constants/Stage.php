<?php
namespace PublicUHC\MinecraftAuth\Protocol\Constants;

use MabeEnum\Enum;

/**
 * Class Stage
 * @package PublicUHC\MinecraftAuth\Protocol\Constants
 *
 * @method static Stage HANDSHAKE()
 * @method static Stage STATUS()
 * @method static Stage LOGIN()
 */
final class Stage extends Enum {

    const HANDSHAKE = 0;
    const STATUS = 1;
    const LOGIN = 2;

} 