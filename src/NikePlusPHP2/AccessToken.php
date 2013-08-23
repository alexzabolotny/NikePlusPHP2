<?php
/**
 * This file is part of library which provides Nike Plus API client for PHP >=5.3.15.
 *
 * Methods, descriptions and additional API information could be found at Nike Plus Developer site:
 * https://developer.nike.com/
 *
 * This project is not affiliated with Nike, Inc.
 *
 * @author Alex Zabolotny <alexander.zabolotny@gmail.com>
 */

namespace NikePlusPHP2;

class AccessToken {
    private $token = "";
    private $refreshToken = "";
    private $expires = 0;

    public function __construct($token, $refreshToken, $expiresIn) {
        $this->expires = time() + $expiresIn; // need to really test this, if expiresIn measured in seconds or something
        $this->refreshToken = $refreshToken;
        $this->token = $token;
    }

    public function __toString() {
        return $this->token;
    }
}
