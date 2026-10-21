<?php

class PayUHash
{
  /**
   * Payment request hash (merchant hosted / S2S).
   * sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
   */
  public static function paymentHash(array $params, string $salt): string
  {
    $key = $params['key'] ?? '';
    $txnid = $params['txnid'] ?? '';
    $amount = $params['amount'] ?? '';
    $productinfo = $params['productinfo'] ?? '';
    $firstname = $params['firstname'] ?? '';
    $email = $params['email'] ?? '';
    $udf1 = $params['udf1'] ?? '';
    $udf2 = $params['udf2'] ?? '';
    $udf3 = $params['udf3'] ?? '';
    $udf4 = $params['udf4'] ?? '';
    $udf5 = $params['udf5'] ?? '';

    $hashString = implode('|', [
      $key, $txnid, $amount, $productinfo, $firstname, $email,
      $udf1, $udf2, $udf3, $udf4, $udf5,
      '', '', '', '', '', '', '',
      $salt,
    ]);

    return strtolower(hash('sha512', $hashString));
  }

  /**
   * Webhook / callback reverse hash verification.
   * sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
   */
  public static function reverseHash(array $params, string $salt): string
  {
    $status = $params['status'] ?? '';
    $udf5 = $params['udf5'] ?? '';
    $udf4 = $params['udf4'] ?? '';
    $udf3 = $params['udf3'] ?? '';
    $udf2 = $params['udf2'] ?? '';
    $udf1 = $params['udf1'] ?? '';
    $email = $params['email'] ?? '';
    $firstname = $params['firstname'] ?? '';
    $productinfo = $params['productinfo'] ?? '';
    $amount = $params['amount'] ?? '';
    $txnid = $params['txnid'] ?? '';
    $key = $params['key'] ?? '';

    $hashString = implode('|', [
      $salt, $status, '', '', '', '', '',
      $udf5, $udf4, $udf3, $udf2, $udf1,
      $email, $firstname, $productinfo, $amount, $txnid, $key,
    ]);

    return strtolower(hash('sha512', $hashString));
  }

  /**
   * Post-service API hash: sha512(key|command|var1|salt)
   */
  public static function commandHash(string $key, string $command, string $var1, string $salt): string
  {
    return strtolower(hash('sha512', $key . '|' . $command . '|' . $var1 . '|' . $salt));
  }
}
