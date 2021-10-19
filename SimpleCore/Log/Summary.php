<?php
namespace SimpleCore\Log;

use HttpRequest;

class Summary
{
  private static ?Summary $instance = null;
  private array $_timeSummary = array();
  private array $_requestHeaders = array();
  private array $_bodyParameters = array();
  private array $_queryParameters = array();
  private array $_toLogHeaders = array('content-type', 'authorization');
  private array $_toAnonymize = array('password', 'user_password', 'authorization');

  private function __construct()
  {
  }

  public static function getInstance(): Summary
  {
    if (self::$instance === null) {
      self::$instance = new Summary();
    }
    return self::$instance;
  }

  public function registerTimer(TimerInterface $timer)
  {
    if (!array_key_exists($timer->getName(), $this->_timeSummary)) {
      $this->_timeSummary[$timer->getName()] = 0;
    }
    $this->_timeSummary[$timer->getName()] += $timer->getTime();
  }

  public function parseRequestObject(HttpRequest $httpRequest)
  {
    $headers = array();
    foreach ($this->_toLogHeaders as $header) {
      if (array_key_exists($header, $httpRequest->getHeaders())) {
        $headers[] = $httpRequest->getHeaders()[$header];
      }
    }
    $this->_requestHeaders = $this->anonymize($headers);
    $this->_bodyParameters = $this->anonymize($httpRequest->getPostFields());
    //no need to anonymize this, it's already in the logs
    $this->_queryParameters = $this->parseQueryParams($httpRequest->getQueryData());
  }

  /**
   * @param string $queryData
   * @return array
   */
  private function parseQueryParams(string $queryData): array
  {
    $return = array();
    $expressions = explode(',', $queryData);
    foreach ($expressions as $expression) {
      $foo = explode('=', $expression);
      $return[$foo[0]] = $foo[1];
    }
    return $return;
  }

  /**
   * hashes the data that we don't want to log, for instance user`s password
   * @param array $array
   * @return array
   */
  private function anonymize(array $array): array
  {
    $return = array();
    foreach ($array as $key => $value) {
      if (in_array($key, $this->_toAnonymize)) {
        $value = sha1($value);
      }
      $return[$key] = $value;
    }
    return $return;
  }
}
