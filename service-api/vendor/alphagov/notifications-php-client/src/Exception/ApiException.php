<?php
namespace Alphagov\Notifications\Exception;

use Psr\Http\Message\ResponseInterface;

class ApiException extends NotifyException {

    /**
     * @var ResponseInterface
     */
    private $response;
    private $errors;
    private $msg;


    public function __construct($message, $code, $body, ResponseInterface $response) {

        $this->response = $response;
        $this->errors = $body["errors"];

        $this->msg = '';
        foreach ($this->errors as $err) {
          $this->msg .= $err['error'] . ': "' . $err['message'] . '"';
        }

        parent::__construct( $this->msg, $code );

    }

    /**
     * Returns the full response the lead to the exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(){
        return $this->response;
    }

    /**
     * Returns an error message summary
     *
     * @return ResponseInterface
     */
    public function getErrorMessage(){
        return $this->msg;
    }

    /**
     * Returns the original error messages from the Api.
     *
     * @return ResponseInterface
     */
    public function getErrors(){
        return $this->errors;
    }

}
