<?php
abstract class KellPro_Base
{
	public $_url;

	public static $classMap = array();

	public function __construct($url = null)
	{
		$this->_url = $url;
	}

	public static function _get($url, $parameters = array(), $className = '')
	{
		if(empty($className))
		{
			$className = preg_replace('/^\//', '', $url);
		}

		$client = new KellPro_Client();
		$response = $client->request(KellPro_Client::GET, $url, $parameters);
		$response->assertValidResponse();

		return KellPro_Base::_responseToObject($response->body, $client, $className);
	}

	public static function _post($url, $parameters = array())
	{
		$client = new KellPro_Client();
		$response = $client->request(KellPro_Client::POST, $url, $parameters);
		$response->assertValidResponse();

		return true;
	}

	public static function _delete($url, $parameters = array())
	{
		$client = new KellPro_Client();
		$response = $client->request(KellPro_Client::DELETE, $url, $parameters);
		$response->assertValidResponse();

		return true;
	}

	public function getUrl()
	{
		return $this->_url;
	}

	private static function _responseToObject($body, $client = null, $resource = null)
	{
		$class = new self::$classMap[$resource];
		preg_match('/[^\/]+$/', $resource, $matches);
		$nodeName = $matches[0];

		$json = json_decode($body);
		if(is_array($json))
		{
			$class->$nodeName = $json;
		}
		elseif(is_object($json))
		{
			foreach($json as $key => $value)
			{

				if($key == 'references')
				{

					foreach($value as &$reference)
					{
						$reference = self::convertReference($reference);
					}
				}

				if($key == 'results')
				{
					foreach($value as $row)
					{
						foreach($row as &$rowValue)
						{
							if(is_object($rowValue) && !empty($rowValue->reference))
							{
								$rowValue = self::convertReference($rowValue->reference);
							}
						}
					}
				}

				if(is_object($value) && !empty($value->reference))
				{
					$value = self::convertReference($value->reference);
				}

				$class->$key = $value;
			}
		}
		else
		{
			$class->$nodeName = $json;
		}

		// Unset any unneeded variables.
		unset($class->_url);

		return $class;
	}


	public static function convertReference($reference)
	{

		$class = new KellPro_Stub($reference);

		return $class;
	}
}