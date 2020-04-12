# Web Request
[![Build Status](https://travis-ci.org/byjg/webrequest.svg?branch=master)](https://travis-ci.org/byjg/webrequest)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c/mini.png)](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c)


A lightweight PSR-7 implementation and and highly customized CURL wrapper for making RESt calls. 

# Main Features

This class implements:
* PSR-7 objects;
* HttpClient customizable with partial implementation PSR-18
* Helper to create Request instances with the most common use cases;
* Wrapper to implement several requests in parallel;

# PSR-7 Implementation and basic usage

Since the implementation follow the PSR7 implementation there is no much explanation about the usage.

The key elements are:
* URI - Will define the URI with parameters, path, host, schema, etc
* Request - Will set the request headers and method;
* Response - Will receive the response header, body and status code. 

More information about the PSR-7 here: https://www.php-fig.org/psr/psr-7/

The implementation to send the request is defined by the class `HttpClient`. This class follow partially the PSR-18 implementation.
So, once you have a Request instance defined just need to call `HttpClient::sendRequest($request);`

## Basic Usage

```php
<?php
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');
$request = \ByJG\Util\Psr7\Request::getInstance($uri);
$response = \ByJG\Util\HttpClient::getInstance()->sendRequest($request);
```

## Passing arguments

```php
<?php
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page')
    ->withQuery(http_build_query(['param'=>'value']));

$request = \ByJG\Util\Psr7\Request::getInstance($uri);
$response = \ByJG\Util\HttpClient::getInstance()->sendRequest($request);
```

# Helper Classes

The WebRequest package has Helper classes to make it easy to create Request instances for some use cases. 

## Passing a string payload (JSON)

```php
<?php
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');
$request = \ByJG\Util\Helper\RequestJson::build(
   $uri,
   "POST",
   '{teste: "value"}'  // Support an associate array
);
$response = \ByJG\Util\HttpClient::getInstance()->sendRequest($request);
```

## Create a Form Url Encoded (emulate HTTP form)

```php
<?php
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');
$request = \ByJG\Util\Helper\RequestFormUrlEncoded::build(
   $uri,
   ["param" => "value"]
);
$response = \ByJG\Util\HttpClient::getInstance()->sendRequest($request);
```

## Create a Multi Part request (upload documents)

```php
<?php
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');

// Define the contents to upload using a list of MultiPartItem objects
$uploadFile = [];
$uploadFile[] = new \ByJG\Util\MultiPartItem('field1', 'value1');
$uploadFile[] = new \ByJG\Util\MultiPartItem(
    'field2',
    '{"key": "value2"}',
    'filename.json',
    'application/json; charset=UTF-8'
);
$uploadFile[] = new \ByJG\Util\MultiPartItem('field3', 'value3');

// Use the Wrapper to create the Request
$request = \ByJG\Util\Helper\RequestMultiPart::build(Uri::getInstanceFromString($uri),
    "POST",
    $uploadFile
);

// Do the request as usual
$response = \ByJG\Util\HttpClient::getInstance()->sendRequest($request);
```

# Customizing the Http Client

The customizations options are:

```php
<?php

$client = \ByJG\Util\HttpClient::getInstance()
    ->withNoFollowRedirect()         // HttpClient will not follow redirects (status codes 301 and 302). Default is follow 
    ->withNoSSLVerification()        // HttpClient will not validate the SSL certificate. Default is validate.
    ->withProxy($uri)                // Define a http Proxy based on the URI. 
    ->withCurlOption($key, $value)   // Set an arbitrary CURL option (use with caution)
;

```


# WebRequestMulti

You can use the HttpClient to do several differents requests in parallel. 

To use this funcionallity you need:

1. Create a instance of the WebRequestMulti class
2. Add the RequestInterface instance
3. Execute

Below a basic example:

```php
<?php
// Create the instances of the requirements
$httpClient = \ByJG\Util\HttpClient::getInstance();

$onSucess = function ($body, $id) {
    // Do something
};

$onError = function ($error, $id) use (&$fail) {
    // Do something
};

// Create the WebRequest Multi
$multi = new \ByJG\Util\WebRequestMulti(
    $httpClient,
    $onSucess,
    $onError
);

// Add the request to run in parallel
$request1 = Request::getInstance($uri1);
$request2 = Request::getInstance($uri2);
$request3 = Request::getInstance($uri3);

$multi
    ->addRequest($request1)
    ->addRequest($request2)
    ->addRequest($request3);

// Start execute and wait to finish
// The results will be get from the closure defined above. 
$multi->execute();
```


# Install

```
composer install "byjg/webrequest=2.0.*"
```

# Running Tests

## Starting the server

```bash
php -S localhost:8080 -t tests/server & 
```

**Note:** It is more assertive create a webserver with the server folder instead to use the PHP built-in webserver.

## Running the integration tests

```php
phpunit
```

## Stopping the server

```php
killall -9 php
```

