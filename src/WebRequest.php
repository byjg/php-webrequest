<?php
/*
*=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
*  Copyright:
*
*  XMLNuke: A Web Development Framework based on XML.
*
*  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
*
*  This file is part of XMLNuke project. Visit http://www.xmlnuke.com
*  for more information.
*
*  This program is free software; you can redistribute it and/or
*  modify it under the terms of the GNU General Public License
*  as published by the Free Software Foundation; either version 2
*  of the License, or (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*
*=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
*/

/**
 * Class to abstract Soap and REST calls
 * @author jg
 *
 */
namespace ByJG\Util;

use Exception;
use InvalidArgumentException;
use SoapClient;
use SoapParam;

class WebRequest
{
	protected $_url;
	protected $_requestUrl;
	protected $_soapClass = null;
	protected $_requestHeader = array();
	protected $_responseHeader = null;
	protected $_cookies = array();
	protected $_lastStatus = "";

	protected $curlOptions = array();

    /**
     * Constructor
     *
     * @param string $url
     * @param array $curlOptions Array of CURL Options
     */
	public function __construct($url, $curlOptions = null)
	{
		$this->_url = $url;
		$this->_requestUrl = $url;

		$this->defaultCurlOptions();
		if (is_array($curlOptions))
		{
			foreach($curlOptions as $key => $value) {
				$this->setCurlOption($key, $value);
			}
		}
	}

	/**
	 * Defines Basic credentials for access the service.
     *
	 * @param string $username
	 * @param string $password
	 */
	public function setCredentials($username, $password)
	{
		$this->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$this->setCurlOption(CURLOPT_USERPWD, $username . ":" . $password);
	}

    /**
     * Get the current CURLOPT_REFERER
     *
     * @return string
     */
	public function getReferer()
	{
		return $this->getCurlOption(CURLOPT_REFERER);
	}
	/**
	 * Set the CURLOPT_REFERER
     *
	 * @param string $value
	 */
	public function setReferer($value)
	{
		$this->setCurlOption(CURLOPT_REFERER, $value);
	}

    /**
     * Get the status of last request (get, put, delete, post)
     *
     * @return integer
     */
	public function getLastStatus()
	{
		return $this->_lastStatus;
	}

    /**
     * Get an array with the curl response header
     *
     * @return array
     */
	public function getResponseHeader()
	{
		return $this->_responseHeader;
	}

	/**
	 * Add a request header
     *
	 * @param string $key
	 * @param string $value
	 */
	public function addRequestHeader($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $newKey=>$newValue)
				$this->addRequestHeader($newKey, $newValue);
		}
		else
		{
			$key = preg_replace_callback('/([\s\-_]|^)([a-z0-9-_])/',
				function($match) {
					return strtoupper($match[0]);
				},
				$key
			);
			$this->_requestHeader[] = "$key: $value";
		}
	}

	/**
	 * Add a cookie
     *
	 * @param string $key
	 * @param string $value If value is null so, try to parse
	 */
	public function addCookie($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $newKey=>$newValue)
				$this->addCookie($newKey, $newValue);
		}
		else
		{
			$value = preg_replace('/(;\s*path=.+)/', '', $value);

			if (is_numeric($key))
				$this->_cookies[] = $value;
			else
				$this->_cookies[] = "$key=$value";
		}
	}

    /**
     * Get the current CURLOPT_FOLLOWLOCATION
     *
     * @return boolean
     */
	public function getFollowLocation()
	{
		return $this->getCurlOption(CURLOPT_FOLLOWLOCATION);
	}
	/**
	 * Set the CURLOPT_FOLLOWLOCATION
     *
	 * @param bool $value
	 */
	public function setFollowLocation($value)
	{
		$this->setCurlOption(CURLOPT_FOLLOWLOCATION, $value);
	}

	/**
	 *
	 * @return SoapClient
	 */
	protected function getSoapClient()
	{
		if ($this->_soapClass == null)
		{
			$this->_soapClass = new SoapClient(NULL,
			array(
				"location" => $this->_url,
				"uri"      => "urn:xmethods-delayed-quotes",
				"style"    => SOAP_RPC,
				"use"      => SOAP_ENCODED
				)
			);

			if ($this->getCurlOption(CURLOPT_HTTPAUTH) == CURLAUTH_BASIC)
			{
				$curlPwd = explode(":", $this->getCurlOption(CURLOPT_USERPWD));
				$username = $curlPwd[0];
				$password = $curlPwd[1];
				$this->_soapClass->setCredentials($username, $password);
			}
		}

		return $this->_soapClass;
	}

	/**
	 * Call a Soap client.
	 *
	 * For example:
	 *
	 * $webreq = new WebRequest("http://www.byjg.com.br/webservice.php/ws/cep");
	 * $result = $webreq->soapCall("obterCep", new array("cep", "11111233"));
	 *
	 * @param string $method
	 * @param array $params
     * @param array $soapOptions
	 * @return string
	 */
	public function soapCall($method, $params = null, $soapOptions = null)
	{
		if (is_array($params))
		{
			$soapParams = array();
			foreach ($params as $key=>$value)
			{
				$soapParams[] = new SoapParam($value, $key);
			}
		}
		else
		{
			$soapParams = null;
		}

		if (!is_array($soapOptions) || ($soapOptions == null))
		{
			$soapOptions = array(
	            "uri" => "urn:xmethods-delayed-quotes",
            	"soapaction" => "urn:xmethods-delayed-quotes#getQuote"
        	);
		}

		// Chamando método do webservice
		$result = $this->getSoapClient()->__call(
			$method,
			$soapParams,
	        $soapOptions
		);

		return $result;
	}

    /**
     * Set the default curl options.
     * You can override this method to setup your own default options.
     * You can pass the options to the constructor also;
     */
	protected function defaultCurlOptions()
	{
		$this->curlOptions[CURLOPT_CONNECTTIMEOUT] = 30;
		$this->curlOptions[CURLOPT_TIMEOUT] = 30;
		$this->curlOptions[CURLOPT_HEADER] = true;
		$this->curlOptions[CURLOPT_RETURNTRANSFER] = true;
		$this->curlOptions[CURLOPT_USERAGENT] = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		$this->curlOptions[CURLOPT_FOLLOWLOCATION] = true;
		$this->curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
		$this->curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
	}

    /**
     * Set a custom CURL option
     *
     * @param int $key
     * @param string $value
     * @throws InvalidArgumentException
     */
	public function setCurlOption($key, $value)
	{
		if (!is_int($key))
		{
			throw new InvalidArgumentException('It is not a CURL_OPT argument');
		}
		if ($key == CURLOPT_HEADER || $key == CURLOPT_RETURNTRANSFER)
		{
			throw new InvalidArgumentException('You cannot change CURLOPT_HEADER or CURLOPT_RETURNTRANSFER');
		}

		if (!is_null($value))
		{
			$this->curlOptions[$key] = $value;
		}
		else
		{
			unset($this->curlOptions[$key]);
		}
	}

    /**
     * Get the current Curl option
     *
     * @param int $key
     * @return mixed
     */
	public function getCurlOption($key)
	{
		return (isset($this->curlOptions[$key]) ? $this->curlOptions[$key] : null);
	}

	protected function setPostString($fields)
	{
		$fields_string = "";

		if (is_array($fields))
		{
			foreach($fields as $key=>$value)
			{
				if (!is_array($value))
					$fields_string .= ($fields_string != "" ? "&" : "") . $key.'='.urlencode($value);
				else
				{
					foreach ($value as $valueItem)
					{
						$fields_string .= ($fields_string != "" ? "&" : "") . $key.'='.urlencode($valueItem);
					}
				}
			}
			$this->setCurlOption(CURLOPT_POSTFIELDS, $fields_string);
		}
		else
		{
			$this->setCurlOption(CURLOPT_POSTFIELDS, $fields);
		}
	}

	protected function setQueryString($fields)
	{
		$this->setPostString($fields);
		$fields_string = $this->getCurlOption(CURLOPT_POSTFIELDS);
		$this->_requestUrl = $this->_url . (strpos($this->_url, "?") === false ? "?" : "&") . $fields_string;
		$this->setCurlOption(CURLOPT_POSTFIELDS, null);
	}


    /**
     * Request the method using the CURLOPT defined previously;
     *
     * @return string
     * @throws Exception
     */
	protected function curlWrapper()
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->_requestUrl);
		$this->_requestUrl = $this->_url;  // Reset request URL

		// Set Curl Options
		foreach ($this->curlOptions as $key => $value)
		{
			curl_setopt($curl, $key, $value);
		}

		// Check if have header
		if (count($this->_requestHeader) > 0)
		{
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_requestHeader);
			$this->_requestHeader = array(); // Reset request Header
		}

		// Add Cookies
		if (count($this->_cookies) > 0)
		{
			curl_setopt($curl, CURLOPT_COOKIE, implode(";", $this->_cookies));
			$this->_cookies = array(); // Reset request Header
		}

		$result = curl_exec($curl);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$this->_lastStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($result === false)
		{
			throw new Exception("CURL - " . curl_error($curl));
		}
		else
		{
			$this->_responseHeader = $this->parseHeader(substr($result, 0, $header_size));
			return substr($result, $header_size);
		}
	}

	protected function parseHeader($raw_headers)
	{
        $headers = array();
        $key = ''; 

        foreach(explode("\n", $raw_headers) as $i => $h)
        {
            $h = explode(':', $h, 2);

            if (isset($h[1]))
            {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]]))
                {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); 
                }
                else
                {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); 
                }

                $key = $h[0]; 
            }
            else 
            { 
                if (substr($h[0], 0, 1) == "\t") 
                    $headers[$key] .= "\r\n\t".trim($h[0]); 
                elseif (!$key) 
                    $headers[0] = trim($h[0]);trim($h[0]); 
            } 
        }
		return $headers;
	}

	protected function clearRequestMethod()
	{
		$this->setCurlOption(CURLOPT_POST, null);
		$this->setCurlOption(CURLOPT_PUT, null);
		$this->setCurlOption(CURLOPT_CUSTOMREQUEST, null);
	}


	/**
	 * Make a REST Get method call
     *
	 * @param array $params
	 * @return string
	 */
	public function get($params = null)
	{
		$this->clearRequestMethod();
		$this->setQueryString($params);
		return $this->curlWrapper();
	}

	/**
	 * Make a REST POST method call with parameters
	 * @param array $params
	 * @return string
	 */
	public function post($params = null)
	{
		$this->clearRequestMethod();
		$this->setCurlOption(CURLOPT_POST, true);
		$this->setPostString($params);
		return $this->curlWrapper();
	}

	/**
	 * Make a REST POST method call sending a payload
	 *
	 * @param string $data
	 * @param string $content_type
	 * @return string
	 */
	public function postPayload($data, $content_type = "text/plain")
	{
		$this->addRequestHeader("Content-Type", $content_type);
		return $this->post($data);
	}

	/**
	 * Make a REST PUT method call with parameters
     *
	 * @param array $params
	 * @return string
	 */
	public function put($params = null)
	{
		$this->clearRequestMethod();
		$this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'PUT');
		$this->setPostString($params);
		return $this->curlWrapper();
	}

	/**
	 * Make a REST PUT method call sending a payload
	 *
	 * @param string $data
	 * @param string $content_type
	 * @return string
	 */
	public function putPayload($data, $content_type = "text/plain")
	{
		$this->addRequestHeader("Content-Type", $content_type);
		return $this->put($data);
	}

	/**
	 * Make a REST DELETE method call with parameters
     *
	 * @param array $params
	 * @return string
	 */
	public function delete($params = null)
	{
		$this->clearRequestMethod();
		$this->setCurlOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
		$this->setPostString($params);
		return $this->curlWrapper();
	}

	/**
	 * Make a REST DELETE method call sending a payload
	 *
	 * @param string $data
	 * @param string $content_type
	 * @return string
	 */
	public function deletePayload($data = null, $content_type = "text/plain")
	{
		$this->addRequestHeader("Content-Type", $content_type);
		return $this->delete($data);
	}

	/**
	 * Makes a URL Redirection based on the current client navigation (Browser)
     *
	 * @param array $params
	 * @param bool $atClientSide If true send a javascript for redirection
	 */
	public function redirect($params = null, $atClientSide = false)
	{
        $this->setQueryString($params);

		ob_clean();
		if (!$atClientSide)
			header('Location: ' . $this->_requestUrl);
		else
		{
			echo "<script language='javascript'>window.top.location = '" . $this->_requestUrl . "'; </script>";
			die();
		}
	}

}
