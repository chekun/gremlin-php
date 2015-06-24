<?php

namespace BrightZone\Rexpro;

use Exception;

/**
 * Gremlin-server PHP Driver client Exception class
 *
 * @category DB
 * @package  gremlin-php
 * @author   Dylan Millikin <dylan.millikin@brightzone.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 apache2
 * @link     https://github.com/tinkerpop/rexster/wiki/RexPro-Messages
 */
class ServerException extends Exception
{
	const NO_CONTENT = 204;
	const MALFORMED_REQUEST = 498;
	const INVALID_REQUEST_ARGUMENTS = 499;
	const SERVER_ERROR = 500;
	const SCRIPT_EVALUATION_ERROR = 597;
	const SERVER_TIMEOUT = 598;
	const SERVER_SERIALIZATION_ERROR = 599;

	public function __construct($message, $code = 0, Exception $previous = null)
	{
		$message = $this->getMessagePerCode($code).' : '. $message;
		parent::__construct($message, $code, $previous);
	}

	private function getMessagePerCode($code)
	{
		$messages = [
			self::NO_CONTENT => "The server processed the request but there is no result to return (e.g. an {@link Iterator} with no elements).",
			self::MALFORMED_REQUEST => "The request message was not properly formatted which means it could not be parsed at all or the 'op' code was not recognized such that Gremlin Server could properly route it for processing. Check the message format and retry the request.",
			self::INVALID_REQUEST_ARGUMENTS => "The request message was parseable, but the arguments supplied in the message were in conflict or incomplete. Check the message format and retry the request.",
			self::SERVER_ERROR => "A general server error occurred that prevented the request from being processed.",
			self::SCRIPT_EVALUATION_ERROR => "The script submitted for processing evaluated in the ScriptEngine with errors and could not be processed. Check the script submitted for syntax errors or other problems and then resubmit.",
			self::SERVER_TIMEOUT => "The server exceeded one of the timeout settings for the request and could therefore only partially responded or did not respond at all.",
			self::SERVER_SERIALIZATION_ERROR => "The server was not capable of serializing an object that was returned from the script supplied on the request. Either transform the object into something Gremlin Server can process within the script or install mapper serialization classes to Gremlin Server.",
		];

		return isset($messages[$code])?$messages[$code]:'';
	}

}
