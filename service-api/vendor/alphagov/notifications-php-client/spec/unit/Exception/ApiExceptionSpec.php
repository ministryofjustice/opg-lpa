<?php
namespace spec\unit\Alphagov\Notifications\Exception;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Alphagov\Notifications\Exception as NotifyException;

use GuzzleHttp\Psr7\Response;

/**
 * Tests for the PHP Notify Client exception.
 *
 * Class ApiExceptionSpec
 * @package spec\Alphagov\Notifications\Exception
 */
class ApiExceptionSpec extends ObjectBehavior
{
    function let(){
      $message = 'HTTP:400';
      $code = 400;
      $body = [
        'status_code' => 400,
        'errors' => [
          [
            'error' => 'BadRequestError',
            'message' => 'Missing personalisation: name'
          ]
        ]
      ];
      $response = new Response(400, ['Content-Type' => 'application/json'], json_encode($body));

      $this->beConstructedWith( $message, $code, $body, $response );
    }

    function it_is_initializable(){
      $this->shouldHaveType('Alphagov\Notifications\Exception\ApiException');
    }

    //----------------------------------------------------------------------------------------------------------
    // Test constructor variations

    function it_provides_access_to_error_details(){
      $this->getCode()->shouldBe( 400 );
      $this->getErrorMessage()->shouldBeString();
      $this->getErrorMessage()->shouldBe( 'BadRequestError: "Missing personalisation: name"' );
      $this->getErrors()->shouldBeArray();
      $this->getErrors()[0]->shouldBeArray();
      $this->getErrors()[0]['error']->shouldBe('BadRequestError');
      $this->getErrors()[0]['message']->shouldBe('Missing personalisation: name');
    }
}
