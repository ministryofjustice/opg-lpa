/**
 * Handle POST /card_details/{paymentId}
 *
 * Redirects to the confirmation page.
 */
var paymentId = context.request.pathParams.paymentId;

respond()
    .withStatusCode(303)
    .withHeader('Location', 'http://localhost:4547/card_details/' + paymentId + '/confirm')
    .withEmpty()
    .skipDefaultBehaviour();
