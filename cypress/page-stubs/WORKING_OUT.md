# If LPA doesn't have an associated payment yet

CheckoutController->payAction()

$paymentClient->createPayment()

HTTP POST request to https://publicapi.payments.service.gov.uk/v1/payments

Request body:

```
{
   "amount":4100,
   "reference":"64346963760",
   "description":"Property and financial affairs LPA for Mrs Nancy Garrison",
   "return_url":"https:\/\/localhost:7002\/lpa\/79650601414\/checkout\/pay\/response"
}
```

Response JSON:

```
{
   "amount":4100,
   "description":"Property and financial affairs LPA for Mrs Nancy Garrison",
   "reference":"64346963760",
   "language":"en",
   "state":{
      "status":"created",
      "finished":false
   },
   "payment_id":"4f03n40mv5i529tolq7jdruus2",
   "payment_provider":"sandbox",
   "created_date":"2023-02-09T17:19:00.590Z",
   "refund_summary":{
      "status":"pending",
      "amount_available":4100,
      "amount_submitted":0
   },
   "settlement_summary":{

   },
   "delayed_capture":false,
   "moto":false,
   "return_url":"https://localhost:7002/lpa/64346963760/checkout/pay/response",
   "authorisation_mode":"web",
   "_links":{
      "self":{
         "href":"https://publicapi.payments.service.gov.uk/v1/payments/4f03n40mv5i529tolq7jdruus2",
         "method":"GET"
      },
      "next_url":{
         "href":"https://www.payments.service.gov.uk/secure/0ed1c8fd-5890-412f-9a29-3a61ca54b8fe",
         "method":"GET"
      },
      "next_url_post":{
         "type":"application/x-www-form-urlencoded",
         "params":{
            "chargeTokenId":"<UUID in standard format>"
         },
         "href":"https://www.payments.service.gov.uk/secure",
         "method":"POST"
      },
      "events":{
         "href":"https://publicapi.payments.service.gov.uk/v1/payments/4f03n40mv5i529tolq7jdruus2/events",
         "method":"GET"
      },
      "refunds":{
         "href":"https://publicapi.payments.service.gov.uk/v1/payments/4f03n40mv5i529tolq7jdruus2/refunds",
         "method":"GET"
      },
      "cancel":{
         "href":"https://publicapi.payments.service.gov.uk/v1/payments/4f03n40mv5i529tolq7jdruus2/cancel",
         "method":"POST"
      }
   }
}
```

Note that this is the same as you get when you lookup an existing payment, but with different status ("created" rather than "started").

The client is then redirected to `_links.next_url.href` (from the above JSON), i.e. https://www.payments.service.gov.uk/secure/0ed1c8fd-5890-412f-9a29-3a61ca54b8fe

This URL returns a 303 to https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2

The page redirected to displays HTML form where the user can insert their payment details. Also note that the client sent a `return_url` in the original POST request to the API.

The form on the HTML page submits a POST request to https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2

```
<div id="card-details-wrap">
<div class="non-web-payment-button-section govuk-!-margin-bottom-5">
<h1 class="govuk-heading-l ">Enter card details</h1>
<div class="payment-summary-wrap">
<div class="payment-summary">
<h2 class="govuk-heading-m">Payment summary</h2>
<p id="payment-description" class="govuk-body">
Property and financial affairs LPA for Mrs Nancy Garrison
</p>
<p class="govuk-body hidden" id="payment-summary-breakdown">
Amount
<span class="govuk-!-font-weight-bold" id="payment-summary-breakdown-amount"></span><br>
Corporate card fee:
<span class="govuk-!-font-weight-bold" id="payment-summary-corporate-card-fee"></span>
</p>
<p class="govuk-body govuk-!-margin-bottom-0">
Total amount:
<span id="amount" class="amount govuk-!-font-size-36 govuk-!-font-weight-bold" data-amount="41.00">£41.00</span>
</p>
</div>
</div>

</div>

<form id="card-details" name="cardDetails" method="POST" action="/card_details/4f03n40mv5i529tolq7jdruus2" novalidate="">
<input id="charge-id" name="chargeId" type="hidden" value="4f03n40mv5i529tolq7jdruus2">
<input id="csrf" name="csrfToken" type="hidden" value="DO4Hsyhp-3ArXDuwonAA7cdPDMob652Ge7d8">

<div class="govuk-form-group card-no-group" data-validation="cardNo">
<label id="card-no-lbl" for="card-no" class="govuk-label govuk-label--s govuk-!-width-three-quarters">
  <span class="card-no-label" data-label-replace="cardNo" data-original-label="Card number">
    Card number
  </span>
</label>

<p class="govuk-body accepted-cards-hint withdrawal-text govuk-!-margin-bottom-0">
    Accepted credit and debit card types
</p>

<ul class="accepted-cards field-empty">
   <li class="visa" data-key="visa" data-credit="true" data-debit="true">
     <img src="/images/transparent-accessibiliity.gif" alt="Visa">
   </li>
   <li class="master-card" data-key="master-card" data-credit="true" data-debit="true">
     <img src="/images/transparent-accessibiliity.gif" alt="Master Card">
   </li>
   <li class="american-express" data-key="american-express" data-credit="true" data-debit="false">
     <img src="/images/transparent-accessibiliity.gif" alt="American Express">
   </li>
   <li class="diners-club" data-key="diners-club" data-credit="true" data-debit="false">
     <img src="/images/transparent-accessibiliity.gif" alt="Diners Club">
   </li>
   <li class="discover" data-key="discover" data-credit="true" data-debit="false">
     <img src="/images/transparent-accessibiliity.gif" alt="Discover">
   </li>
   <li class="jcb" data-key="jcb" data-credit="true" data-debit="false">
     <img src="/images/transparent-accessibiliity.gif" alt="Jcb">
   </li>
   <li class="unionpay" data-key="unionpay" data-credit="true" data-debit="false">
     <img src="/images/transparent-accessibiliity.gif" alt="Unionpay">
   </li>
</ul>

<div class="govuk-inset-text hidden" id="corporate-card-surcharge-message"></div>

<input id="card-no" type="text" inputmode="numeric" pattern="[0-9]*" name="cardNo" maxlength="26" class="govuk-input govuk-!-width-three-quarters" autocomplete="cc-number" value="">
</div>

<div class="govuk-form-group govuk-clearfix govuk-!-margin-bottom-7  expiry-date" data-validation="expiryMonth">
<fieldset class="govuk-fieldset" role="group" aria-describedby="expiry-date-hint">
  <legend>
    <div class="govuk-label govuk-label--s" id="expiry-date-lbl" for="expiry-month">
      <span class="expiry-date-label" data-label-replace="expiryMonth" data-original-label="Expiry date">
        Expiry date
      </span>
    </div>
  </legend>
  <span class="govuk-hint govuk-!-margin-bottom-2" id="expiry-date-hint">
    For example, 10/25</span>
  <div class="govuk-date-input__item govuk-date-input__item--month govuk-date-input__item--with-separator">
    <label class="govuk-label govuk-date-input__label" for="expiry-month">Month</label>
    <input id="expiry-month" type="text" inputmode="numeric" pattern="[0-9]*" name="expiryMonth" value="" class="govuk-input govuk-date-input__input govuk-input--width-3" minlength="1" maxlength="2" autocomplete="cc-exp-month">
  </div>
  <div class="govuk-date-input__item govuk-date-input__item--year">
    <label for="expiry-year" class="govuk-label govuk-date-input__label">Year</label>
    <input id="expiry-year" type="text" inputmode="numeric" pattern="[0-9]*" name="expiryYear" value="" minlength="2" maxlength="4" class="govuk-input govuk-date-input__input govuk-input--width-3" autocomplete="cc-exp-year" data-required="">
  </div>
</fieldset>
</div>
<div class="govuk-form-group" data-validation="cardholderName">
<label id="cardholder-name-lbl" for="cardholder-name" class="govuk-label govuk-label--s">
  <span data-label-replace="cardholderName" data-original-label="Name on card" class="card-holder-name-label">
    Name on card
  </span>
</label>

<input id="cardholder-name" type="text" name="cardholderName" maxlength="200" class="govuk-input govuk-!-width-three-quarters" value="" autocomplete="cc-name">
</div>
<div class="govuk-form-group cvc govuk-clearfix" data-validation="cvc">
<label id="cvc-lbl" for="cvc" class="govuk-label govuk-label--s">
  <span class="cvc-label" data-label-replace="cvc" data-original-label="Card security code">
    Card security code
  </span>
  <span class="govuk-hint govuk-!-margin-bottom-2">
    <span class="generic-cvc">
      The last 3 digits on the back of the card
    </span>
    <span class="amex-cvc hidden">
      <span class="hidden">
        For American Express,
      </span>
      <span class="no-js-lowercase">
        The last 4 digits after the card number on the front
      </span>
    </span>
  </span>
</label>

<input id="cvc" type="text" inputmode="numeric" pattern="[0-9]*" value="" name="cvc" class="govuk-input govuk-input--width-3 cvc" maxlength="4" autocomplete="cc-csc">
<img src="/images/security-code.png" class="generic-cvc" alt="The last 3 digits on the back of the card">
<span class="either hidden">
  or
</span>
<img src="/images/amex-security-code.png" class="amex-cvc hidden" alt="The last 4 digits after the card number on the front">
</div>
<div class="govuk-!-width-three-quarters govuk-!-padding-top-4 govuk-!-margin-top-8 pay-!-border-top">
    <fieldset class="govuk-fieldset" aria-describedby="address-hint">
      <legend>
        <h2 class="govuk-heading-m govuk-!-margin-bottom-1 non-web-payment-button-section">Billing address</h2>
      </legend>

      <div id="address-hint" class="govuk-hint govuk-!-margin-bottom-6">This is the address associated with the card</div>

      <div class="govuk-form-group " data-validation="addressCountry">
        <label id="address-country-lbl" for="address-country" class="govuk-label">
          <span class="address-country-label" data-label-replace="addressCountry" data-original-label="Country or territory">
            Country or territory
          </span>
        </label>


        <div><div class="autocomplete__wrapper"><div style="border: 0px; clip: rect(0px, 0px, 0px, 0px); height: 1px; margin-bottom: -1px; margin-right: -1px; overflow: hidden; padding: 0px; position: absolute; white-space: nowrap; width: 1px;"><div id="address-country__status--A" role="status" aria-atomic="true" aria-live="polite"></div><div id="address-country__status--B" role="status" aria-atomic="true" aria-live="polite"></div></div><input aria-expanded="false" aria-owns="address-country__listbox" aria-autocomplete="both" aria-describedby="address-country__assistiveHint" autocomplete="country-disabled-autocomplete" class="autocomplete__input autocomplete__input--default" id="address-country" name="" placeholder="" type="text" role="combobox"><ul class="autocomplete__menu autocomplete__menu--overlay autocomplete__menu--hidden" id="address-country__listbox" role="listbox"><li aria-selected="false" class="autocomplete__option" id="address-country__option--0" role="option" tabindex="-1" aria-posinset="1" aria-setsize="1"><strong>United Kingdom</strong></li></ul><span id="address-country__assistiveHint" style="display: none;">When autocomplete results are available use up and down arrows to review and enter to select.  Touch device users, explore by touch or with swipe gestures.</span></div></div><select name="addressCountry" class="govuk-select" id="address-country-select" autocomplete="billing country-name" style="display: none;">
            <option value="AF">Afghanistan</option>
            <option value="AL">Albania</option>
            <option value="DZ">Algeria</option>
            <option value="AS">American Samoa</option>
            <option value="AD">Andorra</option>
            <option value="AO">Angola</option>
            <option value="AI">Anguilla</option>
            <option value="AQ">Antarctica</option>
            <option value="AG">Antigua and Barbuda</option>
            <option value="AR">Argentina</option>
            <option value="AM">Armenia</option>
            <option value="AW">Aruba</option>
            <option value="AU">Australia</option>
            <option value="AT">Austria</option>
            <option value="AZ">Azerbaijan</option>
            <option value="BH">Bahrain</option>
            <option value="BD">Bangladesh</option>
            <option value="BB">Barbados</option>
            <option value="BY">Belarus</option>
            <option value="BE">Belgium</option>
            <option value="BZ">Belize</option>
            <option value="BJ">Benin</option>
            <option value="BM">Bermuda</option>
            <option value="BT">Bhutan</option>
            <option value="BO">Bolivia</option>
            <option value="BQ">Bonaire</option>
            <option value="BA">Bosnia and Herzegovina</option>
            <option value="BW">Botswana</option>
            <option value="BV">Bouvet Island</option>
            <option value="BR">Brazil</option>
            <option value="IO">British Indian Ocean Territory</option>
            <option value="VG">British Virgin Islands</option>
            <option value="BN">Brunei</option>
            <option value="BG">Bulgaria</option>
            <option value="BF">Burkina Faso</option>
            <option value="MM">Myanmar (Burma)</option>
            <option value="BI">Burundi</option>
            <option value="KH">Cambodia</option>
            <option value="CM">Cameroon</option>
            <option value="CA">Canada</option>
            <option value="CV">Cape Verde</option>
            <option value="KY">Cayman Islands</option>
            <option value="CF">Central African Republic</option>
            <option value="TD">Chad</option>
            <option value="CL">Chile</option>
            <option value="CN">China</option>
            <option value="CX">Christmas Island</option>
            <option value="CC">Cocos (Keeling) Islands</option>
            <option value="CO">Colombia</option>
            <option value="KM">Comoros</option>
            <option value="CG">Congo</option>
            <option value="CD">Congo (Democratic Republic)</option>
            <option value="CK">Cook Islands</option>
            <option value="CR">Costa Rica</option>
            <option value="HR">Croatia</option>
            <option value="CU">Cuba</option>
            <option value="CW">Curaçao</option>
            <option value="CY">Cyprus</option>
            <option value="CZ">Czechia</option>
            <option value="DK">Denmark</option>
            <option value="DJ">Djibouti</option>
            <option value="DM">Dominica</option>
            <option value="DO">Dominican Republic</option>
            <option value="TL">East Timor</option>
            <option value="EC">Ecuador</option>
            <option value="EG">Egypt</option>
            <option value="SV">El Salvador</option>
            <option value="GQ">Equatorial Guinea</option>
            <option value="ER">Eritrea</option>
            <option value="EE">Estonia</option>
            <option value="ET">Ethiopia</option>
            <option value="FK">Falkland Islands</option>
            <option value="FO">Faroe Islands</option>
            <option value="FJ">Fiji</option>
            <option value="FI">Finland</option>
            <option value="FR">France</option>
            <option value="GF">French Guiana</option>
            <option value="PF">French Polynesia</option>
            <option value="TF">French Southern Territories</option>
            <option value="GA">Gabon</option>
            <option value="GE">Georgia</option>
            <option value="DE">Germany</option>
            <option value="GH">Ghana</option>
            <option value="GI">Gibraltar</option>
            <option value="GR">Greece</option>
            <option value="GL">Greenland</option>
            <option value="GD">Grenada</option>
            <option value="GP">Guadeloupe</option>
            <option value="GU">Guam</option>
            <option value="GT">Guatemala</option>
            <option value="GG">Guernsey</option>
            <option value="GN">Guinea</option>
            <option value="GW">Guinea-Bissau</option>
            <option value="GY">Guyana</option>
            <option value="HT">Haiti</option>
            <option value="HM">Heard Island and McDonald Islands</option>
            <option value="HN">Honduras</option>
            <option value="HK">Hong Kong</option>
            <option value="HU">Hungary</option>
            <option value="IS">Iceland</option>
            <option value="IN">India</option>
            <option value="ID">Indonesia</option>
            <option value="IR">Iran</option>
            <option value="IQ">Iraq</option>
            <option value="IE">Ireland</option>
            <option value="IM">Isle of Man</option>
            <option value="IL">Israel</option>
            <option value="IT">Italy</option>
            <option value="CI">Ivory Coast</option>
            <option value="JM">Jamaica</option>
            <option value="JP">Japan</option>
            <option value="JE">Jersey</option>
            <option value="JO">Jordan</option>
            <option value="KZ">Kazakhstan</option>
            <option value="KE">Kenya</option>
            <option value="KI">Kiribati</option>
            <option value="XK">Kosovo</option>
            <option value="KW">Kuwait</option>
            <option value="KG">Kyrgyzstan</option>
            <option value="LA">Laos</option>
            <option value="LV">Latvia</option>
            <option value="LB">Lebanon</option>
            <option value="LS">Lesotho</option>
            <option value="LR">Liberia</option>
            <option value="LY">Libya</option>
            <option value="LI">Liechtenstein</option>
            <option value="LT">Lithuania</option>
            <option value="LU">Luxembourg</option>
            <option value="MO">Macao</option>
            <option value="MK">North Macedonia</option>
            <option value="MG">Madagascar</option>
            <option value="MW">Malawi</option>
            <option value="MY">Malaysia</option>
            <option value="MV">Maldives</option>
            <option value="ML">Mali</option>
            <option value="MT">Malta</option>
            <option value="MH">Marshall Islands</option>
            <option value="MQ">Martinique</option>
            <option value="MR">Mauritania</option>
            <option value="MU">Mauritius</option>
            <option value="YT">Mayotte</option>
            <option value="MX">Mexico</option>
            <option value="FM">Micronesia</option>
            <option value="MD">Moldova</option>
            <option value="MC">Monaco</option>
            <option value="MN">Mongolia</option>
            <option value="ME">Montenegro</option>
            <option value="MS">Montserrat</option>
            <option value="MA">Morocco</option>
            <option value="MZ">Mozambique</option>
            <option value="NA">Namibia</option>
            <option value="NR">Nauru</option>
            <option value="NP">Nepal</option>
            <option value="NL">Netherlands</option>
            <option value="NC">New Caledonia</option>
            <option value="NZ">New Zealand</option>
            <option value="NI">Nicaragua</option>
            <option value="NE">Niger</option>
            <option value="NG">Nigeria</option>
            <option value="NU">Niue</option>
            <option value="NF">Norfolk Island</option>
            <option value="KP">North Korea</option>
            <option value="MP">Northern Mariana Islands</option>
            <option value="NO">Norway</option>
            <option value="PS">Occupied Palestinian Territories</option>
            <option value="OM">Oman</option>
            <option value="PK">Pakistan</option>
            <option value="PW">Palau</option>
            <option value="PA">Panama</option>
            <option value="PG">Papua New Guinea</option>
            <option value="PY">Paraguay</option>
            <option value="PE">Peru</option>
            <option value="PH">Philippines</option>
            <option value="PN">Pitcairn, Henderson, Ducie and Oeno Islands</option>
            <option value="PL">Poland</option>
            <option value="PT">Portugal</option>
            <option value="PR">Puerto Rico</option>
            <option value="QA">Qatar</option>
            <option value="RO">Romania</option>
            <option value="RU">Russia</option>
            <option value="RW">Rwanda</option>
            <option value="RE">Réunion</option>
            <option value="BL">Saint Barthélemy</option>
            <option value="SH">Saint Helena</option>
            <option value="PM">Saint Pierre and Miquelon</option>
            <option value="MF">Saint-Martin (French part)</option>
            <option value="WS">Samoa</option>
            <option value="SM">San Marino</option>
            <option value="ST">Sao Tome and Principe</option>
            <option value="SA">Saudi Arabia</option>
            <option value="SN">Senegal</option>
            <option value="RS">Serbia</option>
            <option value="SC">Seychelles</option>
            <option value="SL">Sierra Leone</option>
            <option value="SG">Singapore</option>
            <option value="SX">Sint Maarten (Dutch part)</option>
            <option value="SK">Slovakia</option>
            <option value="SI">Slovenia</option>
            <option value="SB">Solomon Islands</option>
            <option value="SO">Somalia</option>
            <option value="ZA">South Africa</option>
            <option value="GS">South Georgia and South Sandwich Islands</option>
            <option value="KR">South Korea</option>
            <option value="SS">South Sudan</option>
            <option value="ES">Spain</option>
            <option value="LK">Sri Lanka</option>
            <option value="KN">St Kitts and Nevis</option>
            <option value="LC">St Lucia</option>
            <option value="VC">St Vincent</option>
            <option value="SD">Sudan</option>
            <option value="SR">Suriname</option>
            <option value="SJ">Svalbard and Jan Mayen</option>
            <option value="SZ">Eswatini</option>
            <option value="SE">Sweden</option>
            <option value="CH">Switzerland</option>
            <option value="SY">Syria</option>
            <option value="TW">Taiwan</option>
            <option value="TJ">Tajikistan</option>
            <option value="TZ">Tanzania</option>
            <option value="TH">Thailand</option>
            <option value="BS">The Bahamas</option>
            <option value="GM">The Gambia</option>
            <option value="TG">Togo</option>
            <option value="TK">Tokelau</option>
            <option value="TO">Tonga</option>
            <option value="TT">Trinidad and Tobago</option>
            <option value="TN">Tunisia</option>
            <option value="TR">Turkey</option>
            <option value="TM">Turkmenistan</option>
            <option value="TC">Turks and Caicos Islands</option>
            <option value="TV">Tuvalu</option>
            <option value="UG">Uganda</option>
            <option value="UA">Ukraine</option>
            <option value="AE">United Arab Emirates</option>
            <option value="GB" selected="selected">United Kingdom</option>
            <option value="US">United States</option>
            <option value="VI">United States Virgin Islands</option>
            <option value="UY">Uruguay</option>
            <option value="UZ">Uzbekistan</option>
            <option value="VU">Vanuatu</option>
            <option value="VA">Vatican City</option>
            <option value="VE">Venezuela</option>
            <option value="VN">Vietnam</option>
            <option value="WF">Wallis and Futuna</option>
            <option value="EH">Western Sahara</option>
            <option value="YE">Yemen</option>
            <option value="ZM">Zambia</option>
            <option value="ZW">Zimbabwe</option>
            <option value="AX">Åland Islands</option>
        </select>
      </div>
      <div class="govuk-form-group address" data-validation="addressLine1">
        <label id="address-line-1-lbl" for="address-line-1" class="govuk-label">
          <span class="address-line-1-label" data-label-replace="addressLine1" data-original-label="Building number or name and street">
                Building number or name and street
          </span>
        </label>


        <input id="address-line-1" type="text" name="addressLine1" maxlength="100" class="govuk-input" value="" autocomplete="billing address-line1">
            <input id="address-line-2" type="text" name="addressLine2" maxlength="100" class="govuk-input govuk-!-margin-top-2" data-last-of-form-group="" value="" aria-label="Enter address line 2" autocomplete="billing address-line2">
      </div>
      <div class="govuk-form-group govuk-!-width-three-quarters" data-validation="addressCity">
        <label id="address-city-lbl" for="address-city" class="govuk-label">
          <span class="address-city-label" data-label-replace="addressCity" data-original-label="Town or city">
            Town or city
          </span>
        </label>


        <input id="address-city" type="text" name="addressCity" maxlength="100" class="govuk-input govuk-!-width-three-quarters" value="" autocomplete="billing address-level2">
      </div>
      <div class="govuk-form-group govuk-!-margin-bottom-0" data-validation="addressPostcode">
        <label id="address-postcode-lbl" for="address-postcode" class="govuk-label">
          <span class="address-postcode-label" data-label-replace="addressPostcode" data-original-label="Postcode">
            Postcode
          </span>
        </label>


        <input id="address-postcode" type="text" name="addressPostcode" maxlength="10" class="govuk-input govuk-!-width-one-quarter" value="" autocomplete="billing postal-code">
      </div>
  </fieldset>
</div>
<div class="govuk-!-width-three-quarters email-container govuk-!-padding-top-4 govuk-!-margin-top-8 pay-!-border-top">
  <fieldset class="govuk-fieldset" aria-describedby="email-hint">
    <div>
      <legend for="email" class="govuk-!-margin-bottom-6">
        <h2 class="govuk-heading-m govuk-!-margin-bottom-1 non-web-payment-button-section">Contact details</h2>
      </legend>
      <div id="email-hint" class="govuk-hint govuk-!-margin-bottom-2">We’ll send your payment confirmation here</div>

      <div class="govuk-form-group " data-validation="email">
        <label id="email-lbl" for="email" class="govuk-label">
          <span class="email-label" data-label-replace="email" data-original-label="Email">
              Email
          </span>
        </label>


        <input id="email" type="email" name="email" maxlength="254" class="govuk-input govuk-!-width-full" value="" autocomplete="email" data-confirmation="true" data-confirmation-label="An email will be sent to: ">
      </div>
    </div>
  </fieldset>
</div>
<div>
  <button data-prevent-double-click="true" class="govuk-button" data-module="govuk-button" id="submit-card-details">
Continue
</button>
</div>
</form>
<form id="cancel" name="cancel" method="POST" action="/card_details/4f03n40mv5i529tolq7jdruus2/cancel" class="form">
<div>
<input id="cancel-payment" type="submit" class="button-reset" value="Cancel payment" name="cancel">
<input id="csrf2" name="csrfToken" type="hidden" value="DO4Hsyhp-3ArXDuwonAA7cdPDMob652Ge7d8">
</div>
</form>
</div>
```

The POST is redirected by a 303 to https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2/confirm, where they confirm their payment. This form is displayed:

```
<div class="confirm-page__content">
          <h1 class="govuk-heading-xl govuk-!-margin-bottom-6">Confirm your payment</h1>

          <div class="payment-summary-wrap govuk-!-margin-bottom-2">
            <div class="payment-summary">
              <h2 class="govuk-heading-m">Payment summary</h2>
              <p id="payment-description" class="govuk-body">
                Property and financial affairs LPA for Mrs Nancy Garrison
              </p>
              <p class="govuk-body govuk-!-margin-bottom-0">
                Total amount:
                <span id="amount" class="amount govuk-!-font-size-36 govuk-!-font-weight-bold">£41.00</span>
              </p>
            </div>
          </div>



            <table class="govuk-table">
  <tbody class="govuk-table__body">
        <tr class="govuk-table__row">
          <th scope="row" class="govuk-table__header">Card number</th>
          <td class="govuk-table__cell govuk-!-width-two-thirds" id="card-number">●●●●●●●●●●●●1111</td>
        </tr>
        <tr class="govuk-table__row">
          <th scope="row" class="govuk-table__header">Expiry date</th>
          <td class="govuk-table__cell govuk-!-width-two-thirds" id="expiry-date">10/25</td>
        </tr>
        <tr class="govuk-table__row">
          <th scope="row" class="govuk-table__header">Name on card</th>
          <td class="govuk-table__cell govuk-!-width-two-thirds" id="cardholder-name">DR E SMITH</td>
        </tr>
        <tr class="govuk-table__row">
          <th scope="row" class="govuk-table__header">Billing address</th>
          <td class="govuk-table__cell govuk-!-width-two-thirds" id="address">88 PARK VIEW ROAD, BIRMINGHAM, b31 5at, United Kingdom</td>
        </tr>
        <tr class="govuk-table__row">
          <th scope="row" class="govuk-table__header">Confirmation email</th>
          <td class="govuk-table__cell govuk-!-width-two-thirds" id="email">elliot.smith@digital.justice.gov.uk</td>
        </tr>
  </tbody>
</table>


          <form id="confirmation" method="POST" action="/card_details/4f03n40mv5i529tolq7jdruus2/confirm" class="form">
            <input id="csrf" name="csrfToken" type="hidden" value="xK2vLoXX-RjaAvQq9O95VztUecaxHSzkWIMg">
            <input id="chargeId" name="chargeId" type="hidden" value="4f03n40mv5i529tolq7jdruus2">
            <button data-prevent-double-click="true" class="govuk-button" data-module="govuk-button" id="confirm">
              Confirm payment

</button>
          </form>

          <form id="cancel" name="cancel" method="POST" action="/card_details/4f03n40mv5i529tolq7jdruus2/cancel" class="form">
            <div>
              <input id="cancel-payment" type="submit" class="button-reset" value="Cancel payment" name="cancel">
              <input id="csrf2" name="csrfToken" type="hidden" value="xK2vLoXX-RjaAvQq9O95VztUecaxHSzkWIMg">
            </div>
          </form>
        </div>
```

This POSTs to /card_details/4f03n40mv5i529tolq7jdruus2/confirm, which does a 303 redirect to https://www.payments.service.gov.uk/return/4f03n40mv5i529tolq7jdruus2, which does a 302 redirect to https://localhost:7002/lpa/64346963760/checkout/pay/response (our original return_url).

CheckoutController->payResponseAction() is invoked

The govpay client does a GET request to `/v1/payments/<payment reference>`. The payment reference is returned by the govpay client when the payment is first created, as the `payment_id` field.

This in turn 302 redirects to https://localhost:7002/lpa/64346963760/complete


# What mountebank needs to do

Mountebank needs to replicate what the govpay API and web app do, namely:

1. Receive POST request for https://publicapi.payments.service.gov.uk/v1/payments to create payment; includes a `return_url` used later to send the user back to Make after their payment is complete. (This will need to be stored, as it's not passed around between forms, but is used later in step 6 to issue a 302 redirect back to Make.) Send response JSON containing a `_links.next_url.href` URL; also contains a `payment_id` field, used to set $lpa->payment->gatewayReference (used for payment lookup later).
2. Receive GET request at the `_links.next_url.href` URL, e.g. https://www.payments.service.gov.uk/secure/0ed1c8fd-5890-412f-9a29-3a61ca54b8fe. Response is a 303 redirect to a card details URL, e.g. https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2 (note that the identifiers in these URLs are generated by govpay; the /secure/ one looks like a UUID)
3. Receive GET request to the card details page at a URL like https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2. The last part of the path is the payment_id which is set as $lpa->payment->gatewayReference. This displays an HTML form which submits to the same /card_details/ URL.
4. Receive POST request at https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2 and 303 redirect to https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2/confirm. This displays an HTML form which submits to the same /confirm URL.
5. Receive POST request at https://www.payments.service.gov.uk/card_details/4f03n40mv5i529tolq7jdruus2/confirm. This does a 303 redirect to https://www.payments.service.gov.uk/return/4f03n40mv5i529tolq7jdruus2.
6. Receive GET request at https://www.payments.service.gov.uk/return/4f03n40mv5i529tolq7jdruus2. Send a 302 redirect to the original `return_url` sent by the user in the POST which created the payment (step 1).

# Creating the mountebank stubs

See mountebank-config.ejs

For first request:

* POST to /v1/payments; save return_url in POST body for later; use amount, reference and description from POST body in JSON response
