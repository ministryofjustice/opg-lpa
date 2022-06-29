from lpaapi import *
import argparse

parser = argparse.ArgumentParser(description="Create a LPA via the API")
parser.add_argument(
    "-hw", action="store_true", default=False, help="Choose Health and Welfare"
)
parser.add_argument("-d", action="store_true", default=False, help="Create Donor")
parser.add_argument("-a", action="store_true", default=False, help="Add attorneys")
parser.add_argument(
    "-asingle", action="store_true", default=False, help="Add single attorney only"
)
parser.add_argument(
    "-r", action="store_true", default=False, help="Add Replacement attorneys"
)
parser.add_argument(
    "-cp", action="store_true", default=False, help="Add Certificate Provider"
)
parser.add_argument(
    "-pn", action="store_true", default=False, help="Add People To Notify"
)
parser.add_argument(
    "-i", action="store_true", default=False, help="Set Instructions and Preferences"
)
parser.add_argument(
    "-w", action="store_true", default=False, help="Set Who is Registering"
)
parser.add_argument("-y", action="store_true", default=False, help="Set Who Are You")
parser.add_argument("-co", action="store_true", default=False, help="Add Correspondent")
parser.add_argument(
    "-ra", action="store_true", default=False, help="Set Repeat Application"
)
parser.add_argument("-pa", action="store_true", default=False, help="Set Payment")
args = parser.parse_args()

if args.hw:
    lpaType = "health-and-welfare"
else:
    lpaType = "property-and-financial"

lpaId = makeNewLpa()
setLpaType(lpaId, lpaType)
# if we specified donor, or we specified attorneys which implies we need a donor, then set the donor
if args.d or args.a:
    setDonor(lpaId)
    # this sets life-sustaining treatment to true
    setPrimaryAttorneyDecisions(lpaId, lpaType)
if args.a:
    addPrimaryAttorney(lpaId)
    addSecondPrimaryAttorney(lpaId, lpaType)
    # this keeps life-sustaining treatment set to true and allows attorneys to act jointly
    setPrimaryAttorneyDecisionsMultipleAttorneys(lpaId, lpaType)
if args.asingle:
    addSecondPrimaryAttorney(lpaId, lpaType)
    setReplacementAttorneysConfirmed(lpaId)
if args.r:
    addReplacementAttorney(lpaId)
    addSecondReplacementAttorney(lpaId)
    setReplacementAttorneyDecisionsMultipleAttorneys(lpaId, lpaType)
    setReplacementAttorneysConfirmed(lpaId)
if args.cp:
    setCertificateProvider(lpaId)
if args.pn:
    addPersonToNotify(lpaId)
if args.i:
    setInstruction(lpaId)
    setPreference(lpaId)
if args.w:
    setWhoIsRegistering(lpaId)
if args.co:
    addCorrespondent(lpaId)
if args.y:
    addWhoAreYou(lpaId)
if args.ra:
    setRepeatApplication(lpaId)
    if not args.hw:
        setRepeatCaseNumber(lpaId)
if args.pa:
    if args.hw:
        setPaymentNormalFeeWithCheque(lpaId)
    else:
        setPaymentLowIncomeClaimingReduction(lpaId)
print(lpaId)
