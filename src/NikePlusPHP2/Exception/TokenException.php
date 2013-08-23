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

namespace NikePlusPHP2\Exception;

class TokenException extends \Exception {
	const ERROR_CANNOT_OBTAIN_TOKEN = 0;

	public function __construct($message = "", $code = 0, \Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}