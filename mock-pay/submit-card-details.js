/**
 * Handle POST /card_details/{paymentId}
 *
 * Redirects to the confirmation page.
 */
var paymentId = context.request.pathParams.paymentId;
var paymentsStore = stores.open('payments');
var returnUrl = paymentsStore.load('return_url_' + paymentId) || '';
var publicBaseUrl = returnUrl.indexOf('://front-ssl/') !== -1 ? 'http://mock-pay:8080' : 'http://localhost:4547';

respond()
    .withStatusCode(303)
    .withHeader('Location', publicBaseUrl + '/card_details/' + paymentId + '/confirm')
    .withEmpty()
    .skipDefaultBehaviour();
