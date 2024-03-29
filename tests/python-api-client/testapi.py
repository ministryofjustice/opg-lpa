from lpaapi import *

lpaId = makeNewLpa()
setLpaType(lpaId, "property-and-financial")
setDonor(lpaId)
setPrimaryAttorneyDecisions(lpaId, "property-and-financial")
addPrimaryAttorney(lpaId)
addSecondPrimaryAttorney(lpaId)
addReplacementAttorney(lpaId)
addSecondReplacementAttorney(lpaId)
setReplacementAttorneyDecisions(lpaId)
setReplacementAttorneysConfirmed(lpaId)
setCertificateProvider(lpaId)
addPersonToNotify(lpaId)
setInstruction(lpaId)
setPreference(lpaId)
setWhoIsRegistering(lpaId)
addCorrespondent(lpaId)
addWhoAreYou(lpaId)
setRepeatApplication(lpaId)
setRepeatCaseNumber(lpaId)
setPayment(lpaId)
setPaymentNormalFeeWithCheque(lpaId)
setPaymentLowIncomeClaimingReduction(lpaId)
setPaymentLowIncomeNotClaimingReduction(lpaId)
getPdf1(lpaId)
deletePrimaryAttorney(lpaId)
deletePrimaryAttorney(lpaId, 2)
deleteReplacementAttorney(lpaId)
deleteLpa(lpaId)
