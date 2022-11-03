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

# donor = donor
# 1 = first primary attorney
parser.add_argument(
    "-w", type=str, choices=["donor", "1"], help="Set Who is Registering"
)

parser.add_argument("-y", action="store_true", default=False, help="Set Who Are You")

# correspondent
parser.add_argument(
    "-co", type=str, choices=["trustcorp", "donor"], help="Add Correspondent"
)

# if true, set a repeat case number
parser.add_argument(
    "-ra", type=str, choices=["true", "false"], help="Set Repeat Application"
)

payment_choices = [
    "on-benefits",
    "normal-pay-by-cheque",
    "low-income-claiming-reduction",
]
parser.add_argument("-pa", type=str, choices=payment_choices, help="Set Payment")

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
    # if registrant is not "donor", API expects an array of attorney IDs
    who = args.w
    if who != "donor":
        who = [who]
    setWhoIsRegistering(lpaId, who)
if args.co:
    if args.co == "donor":
        addCorrespondent(lpaId)
    elif args.co == "trustcorp":
        addTrustCorpCorrespondent(lpaId)
if args.y:
    addWhoAreYou(lpaId)
if args.ra:
    # note that invoking this just adds a flag that the repeat application question
    # has been answered; it should always be true
    setRepeatApplication(lpaId)

    if args.ra == "true":
        setRepeatCaseNumber(lpaId)
if args.pa:
    if args.pa == "normal-pay-by-cheque":
        setPaymentNormalFeeWithCheque(lpaId)
    elif args.pa == "low-income-claiming-reduction":
        setPaymentLowIncomeClaimingReduction(lpaId)
    elif args.pa == "on-benefits":
        setPaymentOnBenefits(lpaId)
print(lpaId)
