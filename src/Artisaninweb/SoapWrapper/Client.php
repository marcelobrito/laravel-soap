<?php

namespace Artisaninweb\SoapWrapper;

use SoapClient;

class Client extends SoapClient
{
  /**
   * @var string
   */
  protected $wsdl;

  /**
   * Client constructor.
   *
   * @param string $wsdl
   * @param array  $options
   * @param array  $headers
   */
  public function __construct($wsdl, $options, array $headers = [])
  {
            if (!$options) $options = [];

        $this->_connectionTimeout =
            @$options['connection_timeout']
            ?: ini_get ('default_socket_timeout');
        $this->_socketTimeout =
            @$options['socket_timeout']
            ?: ini_get ('default_socket_timeout');
        unset ($options['socket_timeout']);
    parent::SoapClient($wsdl, $options);

    if (!empty($headers)) {
      $this->headers($headers);
    }
  }

  /**
   * Get all functions from the service
   *
   * @return mixed
   */
  public function getFunctions()
  {
    return $this->__getFunctions();
  }

  /**
   * Get the last request
   *
   * @return mixed
   */
  public function getLastRequest()
  {
    return $this->__getLastRequest();
  }

  /**
   * Get the last response
   *
   * @return mixed
   */
  public function getLastResponse()
  {
    return $this->__getLastResponse();
  }

  /**
   * Get the last request headers
   *
   * @return mixed
   */
  public function getLastRequestHeaders()
  {
    return $this->__getLastRequestHeaders();
  }

  /**
   * Get the last response headers
   *
   * @return mixed
   */
  public function getLastResponseHeaders()
  {
    return $this->__getLastResponseHeaders();
  }

  /**
   * Get the types
   *
   * @return mixed
   */
  public function getTypes()
  {
    return $this->__getTypes();
  }

  /**
   * Get all the set cookies
   *
   * @return mixed
   */
  public function getCookies()
  {
    return $this->__getCookies();
  }

  /**
   * Set a new cookie
   *
   * @param string $name
   * @param string $value
   *
   * @return $this
   */
  public function cookie($name, $value)
  {
    $this->__setCookie($name, $value);

    return $this;
  }

  /**
   * Set the location
   *
   * @param string $location
   *
   * @return $this
   */
  public function location($location = '')
  {
    $this->__setLocation($location);

    return $this;
  }

  /**
   * Set the Soap headers
   *
   * @param array $headers
   *
   * @return $this
   */
  protected function headers(array $headers = [])
  {
    $this->__setSoapHeaders($headers);

    return $this;
  }

  /**
   * Do soap request
   *
   * @param string $request
   * @param string $location
   * @param string $action
   * @param string $version
   * @param string $one_way
   *
   * @return mixed
   */
  public function doRequest($request, $location, $action, $version, $one_way)
  {
      $url_parts = parse_url ($location);
        $host = $url_parts['host'];
        $port =
            @$url_parts['port']
            ?: ($url_parts['scheme'] == 'https' ? 443 : 80);
        $length = strlen ($request);

        // Form the HTTP SOAP request.
        $http_req = "POST $location HTTP/1.0\r\n";
        $http_req .= "Host: $host\r\n";
        $http_req .= "SoapAction: $action\r\n";
        $http_req .= "Content-Type: text/xml; charset=utf-8\r\n";
        $http_req .= "Content-Length: $length\r\n";
        $http_req .= "\r\n";
        $http_req .= $request;

        // Need to tell fsockopen to use SSL when requested.
        if ($url_parts['scheme'] == 'https')
            $host = 'ssl://'.$host;

        // Open the connection.
        $socket = @fsockopen (
            $host, $port, $errno, $errstr, $this->_connectionTimeout
        );
        if (!$socket)
            throw new SoapFault (
                'Client',
                "Failed to connect to SOAP server ($location): $errstr"
            );

        // Send the request.
        stream_set_timeout ($socket, $this->_socketTimeout);
        fwrite ($socket, $http_req);

        // Read the response.
        $http_response = stream_get_contents ($socket);

        // Close the socket and throw an exception if we timed out.
        $info = stream_get_meta_data ($socket);
        fclose ($socket);
        if ($info['timed_out'])
            throw new SoapFault (
                'Client',
                "HTTP timeout contacting $location"
            );

        // Extract the XML from the HTTP response and return it.
        $response = preg_replace (
            '/
                \A       # Start of string
                .*?      # Match any number of characters (as few as possible)
                ^        # Start of line
                \r       # Carriage Return
                $        # End of line
             /smx',
            '', $http_response
        );
        return $response;
  }

  /**
   * Do a soap call on the webservice client
   *
   * @param string $function
   * @param array  $params
   *
   * @return mixed
   */
  public function call($function, $params)
  {
    return call_user_func_array([$this, $function], $params);
  }

  /**
   * Allias to do a soap call on the webservice client
   *
   * @param string $function
   * @param array  $params
   * @param array  $options
   * @param null   $inputHeader
   * @param null   $outputHeaders
   *
   * @return mixed
   */
  public function SoapCall($function,
    array $params,
    array $options = null,
    $inputHeader = null,
    &$outputHeaders = null
  ) {
    return $this->__soapCall($function, $params, $options, $inputHeader, $outputHeaders);
  }
}
