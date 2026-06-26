/**
 * Handle POST /v1/payments
 *
 * Stores the return_url for later use and returns a payment response
 * whose next_url points to this mock's own card-entry web page.
 */
var requestBody = JSON.parse(context.request.body);

// Generate a unique payment ID
var paymentId = 'pay' + Date.now().toString(36) + Math.random().toString(36).substring(2, 7);

var returnUrl = requestBody.return_url;
var amount    = requestBody.amount    || 0;
var reference = requestBody.reference || '';
var description = requestBody.description || '';

// Persist data so later requests can use it
var paymentsStore = stores.open('payments');
paymentsStore.save('return_url_' + paymentId, returnUrl);
paymentsStore.save('reference_' + paymentId, reference);
paymentsStore.save('email_' + paymentId, requestBody.email || 'payer@example.org');

var baseUrl = 'http://mock-pay:8080';

var responseBody = JSON.stringify({
    payment_id: paymentId,
    amount: amount,
    reference: reference,
    description: description,
    return_url: returnUrl,
    language: 'en',
    state: { status: 'created', finished: false },
    payment_provider: 'sandbox',
    created_date: new Date().toISOString(),
    refund_summary: { status: 'pending', amount_available: amount, amount_submitted: 0 },
    settlement_summary: {},
    delayed_capture: false,
    moto: false,
    _links: {
        self: {
            href: baseUrl + '/v1/payments/' + paymentId,
            method: 'GET'
        },
        next_url: {
            href: baseUrl + '/card_details/' + paymentId,
            method: 'GET'
        },
        next_url_post: {
            href: baseUrl + '/card_details/' + paymentId,
            method: 'POST',
            params: { chargeTokenId: paymentId },
            type: 'application/x-www-form-urlencoded'
        },
        events: {
            href: baseUrl + '/v1/payments/' + paymentId + '/events',
            method: 'GET'
        },
        refunds: {
            href: baseUrl + '/v1/payments/' + paymentId + '/refunds',
            method: 'GET'
        },
        cancel: {
            href: baseUrl + '/v1/payments/' + paymentId + '/cancel',
            method: 'POST'
        }
    }
});

respond()
    .withStatusCode(201)
    .withHeader('Content-Type', 'application/json')
    .withContent(responseBody)
    .skipDefaultBehaviour();
