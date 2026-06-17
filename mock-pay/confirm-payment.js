/**
 * Handle POST /card_details/{paymentId}/confirm
 *
 * Loads the return_url stored during payment creation and redirects
 * the browser back to the LPA app.
 */
var paymentId = context.request.pathParams.paymentId;

var paymentsStore = stores.open('payments');
var returnUrl = paymentsStore.load('return_url_' + paymentId) || '';

respond()
    .withStatusCode(303)
    .withHeader('Location', returnUrl)
    .withEmpty()
    .skipDefaultBehaviour();
