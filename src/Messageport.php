<?php

namespace Edgility\Messageport;

/**
 * MessagePort API class
 *
 * API Documentation: http://edgility.com.au
 * MessagePort Information: http://edgility.com.au
 *
 * @author Edgility <https://github.com/edgility>
 * @version 1.0
 *
 */
class Messageport {

    /**
     * The API base URL
     */
    const API_URL = 'http://messageport.com.au/rest/v1/';

    /**
     * The API Unique Id
     * 
     * @var string
     */
    private $_unqiueId;

    /**
     * The API Unique Password
     * 
     * @var string
     */
    private $_uniquePassword;

    private $_obj;
    private $_response;
    private $_notice;

    /**
     * API Keys can be generated at https://messageport.com.au/apis/
     *
     * @param string $uniqueId          MessagePort unique id API key 
     * @param string $uniquePassword    MessagePort unique password API key 
     *
     * @return void
     */
    public function __construct($uniqueId, $uniquePassword) {
        $this->_unqiueId = $uniqueId;
        $this->_uniquePassword = $uniquePassword;
    }

    /**
     * Send an SMS to a mobile handset.
     *
     * @param string  $message              Non-Unicode message to be sent to the handset
     * @param integer $mobileNumber         Mobile number in international format. To send
     *                                      to the mobile 0491 570 156, submit 61491570156
     * @param string [optional]  $reference If sending a two way message replies will be pushed
     *                                      back to your system with your reference id.
     * @param string [optional]  $senderId  When null the system will send a two
     *                                      way message from our shared-pool of mobile 
     *                                      numbers. Optional to send from a valid Alpha/Numeric
     *                                      source.
     *
     * @return mixed
     */

    public function sendMessage($message, $mobileNumber, $reference = null, $senderId = null) {
        return $this->_makeRequest('messages', 'POST', array('messaage' => $message, 'number' => $mobileNumber, 'reference' => $reference, 'senderid' => $senderId));
    }

    /**
     * Check the balance and get other information about the authenticated user.
     *
     * @return mixed
     */

    public function checkBalance() {
        return $this->_makeRequest('users', 'GET');
    }

    /**
     * Get human readable responses from the last API call
     *
     * @return string
     */

    public function getCleanResponse() {
        return $this->_notice;
    }

    /**
     * Make API curl request.
     *
     * @param string  $function             API resouce path
     * @param string  $method               Request type GET|POST
     * @param array [optional] $params      Additional request parameters
     *
     * @return mixed
     */

    private function _makeRequest($function, $method, $params = array()) {
        if (is_array($params)) {
            $paramString = http_build_query($params);
        }

        $apiCall = self::API_URL . $function . (('GET' === $method) ? $paramString : null);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiCall);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_unqiueId.':'.$this->_uniquePassword); 

        if ('POST' === $method) {
          curl_setopt($ch, CURLOPT_POST, count($params));
          curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        }

        $jsonData = curl_exec($ch);

        if (false === $jsonData) {
          throw new \Exception("Error: _makeRequest() - cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        $this->_decodeResponse($jsonData);

        return json_decode( $jsonData );
    }

    /**
     * Decode JSON response
     *
     * @param json  $jsonData   JSON encoded string returned from the API
     *
     * @return null
     */

    private function _decodeResponse($jsonData) {
        $this->_obj = json_decode( $jsonData );

        $response = '';

        switch ($this->_obj->code) {
            case 200:
                $this->_response = 'Success';
                $this->_notice = '';
                break;
            case 400:
                $this->_response = 'Bad Request';

                foreach($this->_obj->errors->children as $errors) {
                    foreach($errors as $error) {
                        $response.= join($error, ', ').'. ';
                    }
                }

                $this->_notice = rtrim(trim($response), '.').'.';
                break;
            case 402:
                $this->_response = 'Payment Required';
                $this->_notice = '';
                break;
            case 403:
                $this->_response = 'Forbidden';
                
                foreach($this->_obj->errors as $error) {
                    $response.= $error.'. ';
                }

                $this->_notice = rtrim(trim($response), '.').'.';
                break;
            case 405:
                $this->_response = 'Method Not Allowed';
                $this->_notice = '';
                break;
            case 406:
                $this->_response = 'Not Acceptable';
                $this->_notice = '';
                break;
            case 409:
                $this->_response = 'Conflict';
                $this->_notice = '';
                break;
            default:
                $this->_response = 'Unhandled Response';
                $this->_notice = '';
                break;
        }
    }
}