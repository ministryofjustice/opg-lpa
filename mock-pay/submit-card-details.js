/**
 * Handle POST /card_details/{paymentId}
 *
 * Redirects to the confirmation page.
 */
var paymentId = context.request.pathParams.paymentId;

respond()
    .withStatusCode(303)
    .withHeader('Location', 'http://mock-pay:8080/card_details/' + paymentId + '/confirm')
    .withEmpty()
    .skipDefaultBehaviour();
