<?php

namespace BrightZone\Rexpro;

/**
 * Gremlin-server PHP Driver client Messages class
 * Builds and parses binary messages for communication with RexPro
 * Use example:
 *
 * ~~~
 * $message = new Messages;
 * $message->gremlin = 'g.V';
 * $message->op = 'eval';
 * $message->processor = '';
 * $message->setArguments(['language'=>'gremlin-groovy']);
 * $message->registerSerializer('\brightzone\rexpro\serializers\Json');
 * // etc ...
 * $db = new Connection;
 * $db->open();
 * $db->send($message);
 * ~~~
 *
 * @category DB
 * @package  gremlin-php
 * @author   Dylan Millikin <dylan.millikin@brightzone.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 apache2
 * @link     https://github.com/tinkerpop/rexster/wiki/RexPro-Messages
 */
class Messages
{
	/**
	 * @var array basic message configuration
	 *  ie: op,processor,requestId, etc..
	 */
	public $configuration = [];

	/**
	 * @var array args of the message
	 */
	public $args = [];

	/**
	 * @var array list of serializers loaded for this instance
	 */
	private $_serializers = [];

	/**
	 * Overriding construct to populate _serializer
	 *
	 * @param int $serializer serializer object to use for packing and unpacking of messages
	 *
	 * @return void
	 */
	public function __construct()
	{
		//set default values for message
		$this->setDefaults();
	}

	/**
	 * Sets default values to this message
	 * The values are :
	 *  - gremlin   : ''
	 *  - op        : 'eval'
	 *  - processor : ''
	 *
	 * @return void
	 */
	private function setDefaults()
	{
		$this->gremlin = '';
		$this->op = 'eval';
		$this->processor = '';
	}

	/**
	 * magic setter to populate $this->configuration + gremlin arg
	 *
	 * @param string $name  name of the variable you want to set
	 * @param string $value value of the variable you want to set
	 *
	 * @return void
	 */
	public function __set($name,$value)
	{
		if($name == 'gremlin')
		{
			$this->args['gremlin'] = $value;
		}
		else
		{
			$this->configuration[$name] = $value;
		}
	}

	/**
	 * magic getter to fetch $this->configuration + gremlin arg
	 *
	 * @param string $name name of the variable you want to get
	 *
	 * @return string
	 */
	public function __get($name)
	{
		if($name == 'gremlin' && isset($this->args['gremlin']))
		{
			return $this->args['gremlin'];
		}
		else
		{
			if(isset($this->configuration[$name]))
			{
				return $this->configuration[$name];
			}
		}
	}

	/**
	 * magic isset to return proper setting of $this->configuration + gremlin arg
	 *
	 * @param string $name name of the variable you want to check
	 *
	 * @return string
	 */
	public function __isset($name)
	{
		if($name == 'gremlin')
		{
			return isset($this->args['gremlin']);
		}
		else
		{
			return isset($this->configuration[$name]);
		}
		return FALSE;
	}

	/**
	 * Setter method for arguments
	 * This will replace existing entries.
	 *
	 * @param array $array collection of arguments
	 *
	 * @return void
	 */
	public function setArguments($array)
	{
		$this->args = array_merge($this->args, $array);
	}

	/**
	 * Create and set request UUID
	 *
	 * @return string the UUID
	 */
	public function createUuid()
	{
		return $this->requestUuid = Helper::createUuid();
	}

	/**
	 * Constructs full binary message for use in script execution
	 *
	 * @return string Returns binary data to be packed and sent to socket
	 */
	public function buildMessage()
	{
		//lets start by packing message
		$this->createUuid();

		//build message array
		$message = array(
				'requestId' => $this->requestUuid,
				'processor' => $this->processor,
				'op' => $this->op,
				'args' => $this->args
				);
		//serialize message
		if(!isset($this->_serializers['default']))
		{
			throw new InternalException("No default serializer set", 500);
		}
		$this->_serializers['default']->serialize($message);
		$mimeType = $this->_serializers['default']->getMimeType();

		$finalMessage =  pack('C',16).$mimeType.$message;
		return $finalMessage;
	}

	/**
	 * Parses full message (including outter envelope)
	 *
	 * @param string $payload  payload from the server response
	 * @param bool   $isBinary whether we should expect binary data (TRUE) or plein text (FALSE)
	 *
	 * @return array Array containing all results
	 */
	public function parse($payload, $isBinary)
	{
		if($isBinary)
		{
			list($mimeLength) = unpack('C',$payload[0]);
			$mimeType = substr($payload, 1, $mimeLength + 1);
			$serializer = $this->getSerializer($mimeType);
			$payload = substr($payload, $mimeLength + 1, strlen($payload));

			return $serializer->unserialize($payload);
		}
		return $this->_serializers['default']->unserialize($payload);
	}

	/**
	 * Get the serializer object depending on a provided mimeType
	 *
	 * @param string $mimeType the mimeType of the serializer you want to retrieve
	 *
	 * @return SerializerInterface serializer object.
	 */
	public function getSerializer($mimeType = '')
	{
		if($mimeType == '')
		{
			return $this->_serializers['default'];
		}
		foreach($this->_serializers as $serializer)
		{
			if($serializer->getMimeType() == $mimeType)
			{
				return $serializer;
			}
		}
		return FALSE;
	}

	/**
	 * Register a new serializer to this object
	 *
	 * @param mixed $value   either a serializer object or a string of the class name (with namespace)
	 * @param bool  $default whether or not to use this serializer as the default one
	 *
	 * @return void
	 */
	public function registerSerializer($value, $default = TRUE)
	{
		if(is_string($value) && class_exists($value))
		{
			$value = new $value();
		}

		if(in_array('BrightZone\Rexpro\Serializers\SerializerInterface', class_implements($value)))
		{
			if($default)
			{
				$this->_serializers['default'] = $value;
			}
			else
			{
				$this->_serializers[] = $value;
			}
		}
	}

	/**
	 * Binds a value to be used inside gremlin script
	 *
	 * @param string $bind  The binding name
	 * @param mixed  $value the value that the binding name refers to
	 *
	 * @return void
	 */
	public function bindValue($bind,$value)
	{
		if(!isset($this->args['bindings']))
		{
			$this->args['bindings'] = [];
		}
		$this->args['bindings'][$bind]=$value;
	}

	/**
	 * Clear this message and start anew
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->configuration = [];
		$this->args = [];
		$this->setDefaults();
	}
}
