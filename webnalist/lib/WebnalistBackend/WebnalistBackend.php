<?php

/**
 * Class Webnalist
 *
 * This library together with WebnalistFrontend enables the processing of WebnalistPayment using remote account model.
 * User don't need to be logged in Merchant service.
 *
 * @copyright Webnalist Sp. z o. o.
 * @package Webnalists
 * @author Mateusz Dołęga <mateusz.dolega@webnalist.com>
 */
class WebnalistBackend
{
    const SERVICE_URL = 'https://webnalist.com';
    const PARAM_PREFIX = 'wn_';

    private $purchaseId;
    private $token;
    private $debug;
    private $sandbox;
    private $publicKey;
    private $secretKey;
    private $url;
    private $purchasePath;

    /**
     * @param string $wnPublicKey Public merchant key
     * @param string $wnSecretKey Private merchant key
     * @param bool $debug Print errors
     * @param bool $sandbox Sandbox mode
     */
    public function __construct($wnPublicKey, $wnSecretKey, $debug = false, $sandbox = false)
    {
        $this->publicKey = $wnPublicKey;
        $this->secretKey = $wnSecretKey;
        $this->sandbox = $sandbox;
        $this->url = self::SERVICE_URL;
        $this->debug = $debug;
        $this->purchaseId = isset($_REQUEST[self::PARAM_PREFIX . 'purchase_id']) ?
            (int)$_GET[self::PARAM_PREFIX . 'purchase_id'] : null;
        $this->token = isset($_REQUEST[self::PARAM_PREFIX . 'token']) ?
            $_GET[self::PARAM_PREFIX . 'token'] : null;

        if (!$sandbox) {
            $this->purchasePath = '/api/merchant/article/voter.json';
        } else {
            $this->purchasePath = '/sandbox/validate.php';
        }

    }

    /**
     * Remote service url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Override webnalist service url
     *
     * @param string $url
     * @return string
     */
    public function setUrl($url = null)
    {
        if ($url) {
            $this->url = $url;
        }
    }

    /**
     * Call remote Webnalist api using pair of the keys.
     *
     * @param $url
     * @param array $params
     * @return mixed
     * @throws CurlErrorException
     * @throws WebnalistException
     */
    private function exec($url, $params = array())
    {
        if (!is_callable('curl_init')) {
            throw new WebnalistException('Method curl_init is not callable. Curl module is required to communication.');
        }
        $params['public_key'] = $this->publicKey;
        $params['secret_key'] = $this->secretKey;
        $query = http_build_query($params);
        $url = sprintf('%s?%s', $url, $query);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.99 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($code !== 200) {
            if (!$error || $error = 22) { //http error
                if ($this->debug) {
                    var_dump('error', $error);
                    var_dump('errorCode', $code);
                    if($code == 404){
                        var_dump('Page '.$url.' is not found.');
                    }
                }
                throw new CurlErrorException(CurlErrorException::codeToMessage($code), $code);
            } else {
                if ($this->debug) {
                    var_dump('error', $error);
                    var_dump('errorCode', $code);
                }
                throw new CurlErrorException($error, $code);
            }
        }

        return $response;
    }

    /**
     * Ask remote Webnalist service if purchase id is valid for requested single usage token.
     *
     * @return bool
     * @throws CurlErrorException
     * @throws WebnalistException
     */
    private function vote()
    {
        $params = array(
            self::PARAM_PREFIX . 'purchase_id' => $this->purchaseId,
            self::PARAM_PREFIX . 'token' => $this->token
        );
        $response = $this->exec($this->getUrl() . $this->purchasePath, $params);
        if ($response) {
            $response = json_decode($response);
            if ($response->isAllowed) {
                return true;
            } else {
                throw new WebnalistException($response->message);
            }
        }

        return false;
    }

    /**
     * Shortcut to vote() method.
     *
     * @return bool
     * @throws WebnalistException
     */
    public function canRead()
    {
        if ($this->token && $this->purchaseId) {
            return $this->vote();
        }

        return false;

    }

}

/**
 * Class WebnalistException
 *
 * For better errors processing.
 */
class WebnalistException extends Exception
{
}

/**
 * Class CurlErrorException
 *
 * For better errors processing.
 * @todo better errors processing
 */
class CurlErrorException extends WebnalistException
{
    static $messages = array(
        '000' => 'Nieznany błąd komunikacji z systemem Webnalist.com',
        '403' => 'Nieprawidłowe dane dostępowe',
        '404' => 'Nie znaleziono strony',
    );

    public static function codeToMessage($code)
    {
        return array_key_exists($code, self::$messages) ? self::$messages[$code] : self::$messages['000'];
    }

}