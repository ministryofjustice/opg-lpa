/**
 * Handle GET /v1/payments/{paymentId}
 *
 * Returns a payment status of "success" so the LPA app accepts the payment.
 */
var paymentId = context.request.pathParams.paymentId;

var paymentsStore = stores.open('payments');
var returnUrl  = paymentsStore.load('return_url_' + paymentId) || '';
var reference  = paymentsStore.load('reference_' + paymentId) || '';
var email      = paymentsStore.load('email_' + paymentId) || 'payer@example.org';

var baseUrl = 'http://localhost:4547';

var responseBody = JSON.stringify({
    payment_id: paymentId,
    amount: 2300,
    reference: reference,
    email: email,
    state: { status: 'success', finished: true },
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
