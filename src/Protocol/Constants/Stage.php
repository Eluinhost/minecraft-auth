<?php

namespace PublicUHC\MinecraftAuth\Protocol;

use SplEnum;

class Stage extends SplEnum {

    const __default = self::HANDSHAKE;

    const HANDSHAKE = 0;
    const STATUS = 1;
    const LOGIN = 2;

} 