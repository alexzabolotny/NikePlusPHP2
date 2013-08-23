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

use NikePlusPHP2\Exception\RequestException;
use NikePlusPHP2\Exception\TokenException;

class Api {
	const API_ENDPOINT = "https://developer.nike.com/request/";

	const METHOD_URL_GENERAL_DATA = "/me/sport";
	const METHOD_URL_ACTIVITIES = "/me/sport/activities";
	const METHOD_URL_ACTIVITY_DETAILS = "/me/sport/activities/%s";
	const METHOD_URL_ACTIVITY_GPS = "/me/sport/activities/%s/gps";

	private $email = "";
	private $password = "";

	private $UA = "Mozilla/5.0";

	private $loginCookies = "";
	private $accessToken = null;

	public function __construct($email, $password) {
		$this->email = $email;
		$this->password = $password;

		$this->init();
	}

	private function init() {
		$this->login();
		$this->obtainAccessToken();
	}

	/**
	 * Obtaining access token and saving into private variable for later use.
	 *
	 * @throws TokenException
	 */
	private function obtainAccessToken() {
		$data = "data=" . urlencode('{"method":"POST","url":"%base_url%/nsl/v2.0/user/login?format=json&app=%25appid%25&client_id=%25client_id%25&client_secret=%25client_secret%25","headers":{"appid":"%appid%","Accept":"application/json","Content-Type":"application/x-www-form-urlencoded"},"body":"email=' . urlencode($this->email) . '&password=' . urlencode($this->password) . '"}');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_COOKIE, $this->loginCookies);
		curl_setopt($ch, CURLOPT_URL, self::API_ENDPOINT);
		$response = curl_exec($ch);

		$r = json_decode($response);
		if ($r === false) {
			throw new TokenException("Cannot obtain access token.", TokenException::ERROR_CANNOT_OBTAIN_TOKEN);
		}
		$b = json_decode($r->response->body);
		if ($b === false) {
			throw new TokenException("Cannot obtain access token.", TokenException::ERROR_CANNOT_OBTAIN_TOKEN);
		}

		$this->accessToken = new AccessToken(
			$b->access_token,
			$b->refresh_token,
			$b->expires_in
		);
	}

	/**
	 * Since there is no other way of getting access token but just login to Nike developer site and get session cookie,
	 * then using built-in ajax response to request token which will be used during this session. Look at method
	 * @method obtainAccessToken() for details.
	 */
	private function login() {
		$data = "email=" . urlencode($this->email) . "&password=" . urlencode($this->password);

		$url = "https://developer.nike.com/login";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->UA);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response = curl_exec($ch);

		preg_match_all('|Set-Cookie: (.*);|U', $response, $matches);
		$this->loginCookies = implode('; ', $matches[1]);
	}

	/**
	 *
	 * Makes a generic request to Nike API. Returns object which is a response body or raw response body if third param
	 * is set to true.
	 *
	 * @param string $url - Nike API method url.
	 * @param array $params - associative array of parameters which to pass with method call.
	 * @param boolean $return_raw - if true, return raw API response with headers. No validation performed. Be careful.
	 * @return mixed - object of response body or JSON string if third param is true.
	 * @throws RequestException
	 */
	private function request($url, $params = array(), $return_raw = false) {
		$req = array_merge(array('access_token' => (string) $this->accessToken), $params);
		$paramString = http_build_query($req);

		$data = "data=" . urlencode('{"method":"GET","url":"%base_url%' . $url . '?' . $paramString . '","headers":{"appid":"%appid%","Accept":"application/json"},"body":""}');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_COOKIE, $this->loginCookies);
		curl_setopt($ch, CURLOPT_URL, self::API_ENDPOINT);
		$response = curl_exec($ch);

		//@TODO fix all this squashy error handling
		if ($response === false || curl_errno($ch) != 0) {
			$curlError = curl_error($ch);
			curl_close($ch);
			throw new RequestException($curlError);
		}

		if ($return_raw) return $response;

		$r = json_decode($response);
		if ($r === false) {
			$curlError = curl_error($ch);
			curl_close($ch);
			throw new RequestException($curlError);
		}
		if (!empty($r->error) && !is_null($r->error)) {
			throw new RequestException($r->error->code);
		}

		$b = json_decode($r->response->body);
		if (!empty($b->error) && !is_null($b->error)) {
			throw new RequestException($b->error);
		}
        if (isset($b->result) && $b->result == "failure") {
            throw new RequestException($b->errorMessage);
        }
		if ($b === false) {
			throw new RequestException("Response cannot be used as JSON: " . $this->getJSONLastErrorMessage());
		}

		return $b;
	}

	/**
	 * This method returns what Nike calls "Aggregate Sport Data"
	 * (https://developer.nike.com/activities/get_aggregate_sport_data).
	 *
	 * @return mixed
	 */
	public function generalData() {
		return $this->request(self::METHOD_URL_GENERAL_DATA);
	}

	/**
	 * Retrieve $count activities starting from $offset. Default values correspond to such as listed at:
	 * https://developer.nike.com/activities/list_users_activities
	 *
	 * @param int $offset
	 * @param int $count
	 * @return mixed
	 */
	public function activitiesOffsetLimit($offset = 1, $count = 5) {
		return $this->request(self::METHOD_URL_ACTIVITIES, array("offset" => $offset, "count" => $count));
	}

    /**
     * Retrieve activities bound by $start_date and $end_date. The result contains pagination, use returned object's
     * $paging->next field to get next page url.
     *
     * @param $start_date - date in ISO8601 standard format, yyyy-mm-dd.
     * @param $end_date - date in ISO8601 standard format, yyyy-mm-dd.
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function activitiesDateFromTo($start_date, $end_date) {
        if (strtotime($start_date) > strtotime($end_date)) {
            throw new \InvalidArgumentException("End date is less than start date");
        }
		return $this->request(self::METHOD_URL_ACTIVITIES, array("startDate" => $start_date, "endDate" => $end_date));
	}

    /**
     * Fetch activity details by it's ID.
     *
     * @param $activity_id
     * @return mixed
     */
    public function activityDetails($activity_id) {
		return $this->request(sprintf(self::METHOD_URL_ACTIVITY_DETAILS, $activity_id));
	}

    /**
     * Fetch activity GPS points. Contains additional
     *
     * @param $activity_id
     * @return mixed
     */
    public function activityGPS($activity_id) {
		return $this->request(sprintf(self::METHOD_URL_ACTIVITY_GPS, $activity_id));
	}

    /**
     * Workaround for json_last_error_mgs() function for PHP < 5.5.0
     *
     * @return string
     * @throws \Exception
     */
    private function getJSONLastErrorMessage() {
		if (!function_exists('json_last_error_msg')) {
			switch (json_last_error()) {
				case JSON_ERROR_DEPTH:
					return 'Maximum stack depth exceeded';
				case JSON_ERROR_STATE_MISMATCH:
					return 'Underflow or the modes mismatch';
				case JSON_ERROR_CTRL_CHAR:
					return 'Unexpected control character found';
				case JSON_ERROR_SYNTAX:
					return 'Syntax error, malformed JSON';
				case JSON_ERROR_UTF8:
					return 'Malformed UTF-8 characters, possibly incorrectly encoded';
				default:
					throw new \Exception("Undefined error");
			}
		} else {
			return json_last_error_msg();
		}
	}
}