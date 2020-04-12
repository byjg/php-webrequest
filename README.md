# Web Request
[![Build Status](https://travis-ci.org/byjg/webrequest.svg?branch=master)](https://travis-ci.org/byjg/webrequest)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c/mini.png)](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c)


A lightweight PSR-7 implementation and and highly customized CURL wrapper for making RESt calls. 

# Features

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

The WebRequest package have some Helper classes to create Request instances for some use cases. 

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

## Create a Form Url Encoded (emulate <form method="post">)

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
    ->withProxy($uri)                // Define a URI for the Proxy. 
    ->withCurlOption($key, $value)   // Setting arbitrary CURL options (use with caution)
;

```



# WebRequestMulti

You can use the WebRequest to do several differents requests in parallel. 

To use this funcionallity you need:

1. Create a instance of the WebRequestMulti class
2. Add the WebRequest instance
3. Execute

See a basic example to execute the WebRequest and does not care about the result:

```php
<?php
$webRequestMulti = new WebRequestMulti();

$webRequestMulti
    ->addRequest(
        new \ByJG\Util\WebRequest('http://localhost/api/myrest'),
        WebRequestMulti::GET
    )
    ->addRequest(
        new \ByJG\Util\WebRequest('http://anotherserver/method'),
        WebRequestMulti::PUT,
        [
            'param' => 'somevalue',
            'anotherparam' => '30130000'
        ]
    );

$webRequestMulti->execute();
```

You can optionally create a \Closure function for process the successfull and the error result. 

For example:

```php
<?php
$onSuccess = function ($body, $id) {
    echo "[$id] => $body\n";
};

$onError = function ($error, $id) {
    throw new \Exception("$error on id '$id'");
};
```
And then pass to WebRequestMulti constructor:

```php
<?php
$webRequestMulti = new WebRequestMulti($onSuccess, $onError);
```

or pass to the addRequestMethod:

```php
<?php
$webRequestMulti->addRequest(
    $webRequestInstance,
    WebRequestMulti::GET,
    ['params'],
    $onSuccess, 
    $onError
);
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

