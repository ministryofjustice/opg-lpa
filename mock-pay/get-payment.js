/**
 * Handle GET /v1/payments/{paymentId}
 *
 * Returns a payment status of "success" so the LPA app accepts the payment.
 */
var paymentId = context.request.pathParams.paymentId;

var paymentsStore = stores.open('payments');
var returnUrl = paymentsStore.load('return_url_' + paymentId) || '';
var reference = paymentsStore.load('reference_' + paymentId) || paymentId;
var email = paymentsStore.load('email_' + paymentId) || 'payer@example.org';
var action = paymentsStore.load('action_' + paymentId) || 'continue';

var baseUrl = 'http://mock-pay:8080';

var state = {
  continue: { status: 'success', finished: true },
  cancel: { status: 'cancelled', finished: true, code: 'P0030' },
  fail: { status: 'failed', finished: true },
}[action];

var responseBody = JSON.stringify({
  payment_id: paymentId,
  amount: 2300,
  reference: reference,
  email: email,
  state: state,
  payment_provider: 'sandbox',
  created_date: new Date().toISOString(),
  return_url: returnUrl,
  _links: {
    self: {
      href: baseUrl + '/v1/payments/' + paymentId,
      method: 'GET'
    }
  }
});

respond()
  .withStatusCode(200)
  .withHeader('Content-Type', 'application/json')
  .withContent(responseBody)
  .skipDefaultBehaviour();
