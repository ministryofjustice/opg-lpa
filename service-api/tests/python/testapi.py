from lpaapi import *

lpaId = makeNewLpa()
setLpaType(lpaId, 'property-and-financial')
setDonor(lpaId)
setPrimaryAttorneyDecisions(lpaId)
addPrimaryAttorney(lpaId)
addReplacementAttorney(lpaId)
setCertificateProvider(lpaId)
deletePrimaryAttorney(lpaId)
deleteReplacementAttorney(lpaId)
deleteLpa(lpaId)
