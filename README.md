# Web Request
[![Build Status](https://travis-ci.org/byjg/webrequest.svg?branch=master)](https://travis-ci.org/byjg/webrequest)
[![Build Status](https://drone.io/github.com/byjg/webrequest/status.png)](https://drone.io/github.com/byjg/webrequest/latest)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c/mini.png)](https://insight.sensiolabs.com/projects/7cfbd581-fdb6-405d-be0a-afee0f70d30c)

## Description

A lightweight and highly customized CURL wrapper for making RESt calls and a wrapper for call dynamically SOAP requests.
Just one class and no dependencies. 

## Examples

### Basic Usage

```php
<?php
$webRequest = new WebRequest('http://www.example.com/page');
$result = $webRequest->get();
//$result = $webRequest->post();
//$result = $webRequest->delete();
//$result = $webRequest->put();
```

### Passing arguments

```php
<?php
$webRequest = new WebRequest('http://www.example.com/page');
$result = $webRequest->get(['param'=>'value']);
//$result = $webRequest->post(['param'=>'value']);
//$result = $webRequest->delete(['param'=>'value']);
//$result = $webRequest->put(['param'=>'value']);
```

### Passing a string payload (JSON)

```php
<?php
$webRequest = new WebRequest('http://www.example.com/page');
$result = $webRequest->postPayload('{teste: "value"}', 'application/json');
//$result = $webRequest->putPayload('{teste: "value"}', 'application/json');
//$result = $webRequest->deletePayload('{teste: "value"}', 'application/json');
```

### Setting Custom CURL PARAMETER

```php
<?php
$webRequest = new WebRequest('http://www.example.com/page');
$webRequest->setCurlOption(CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
$result = $webRequest->get();
```

### Upload a file using "multipart/form-data"

```php
<?php
$webRequest = new WebRequest('http://www.example.com/page');

// Define the Upload File
$upload = [];
$upload[] = new UploadFile('fieldName', 'fieldContent');
$upload[] = new UploadFile('fieldName', 'fieldContent', 'mime-filename.ext');

// Post and get the result
$result = $webRequest->postUploadFile($upload);
```

### Calling Soap Classes

```php
<?php
$webRequest = new WebRequest('http://www.example.com/soap');
$resutl = $webRequest->soapCall('soapMethod', ['arg1' => 'value']);
```

## WebRequestMulti

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

## Install

Just type: `composer install "byjg/webrequest=1.0.*"`

## Running Tests

### Starting the server

```php
cd tests
php -S localhost:8080 -t tests/server & 
```

**Note:** It is more assertive create a webserver with the server folder instead to use the PHP built-in webserver.

### Running the integration tests

```php
phpunit
```

### Stopping the server

```php
killall -9 php
```
