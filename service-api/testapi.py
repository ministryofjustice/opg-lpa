from lpaapi import *

lpaId = makeNewLpa()
setLpaType(lpaId, 'property-and-financial')
setDonor(lpaId)
setPrimaryAttorneyDecisions(lpaId)
setPrimaryAttorney(lpaId)
setReplacementAttorney(lpaId)
