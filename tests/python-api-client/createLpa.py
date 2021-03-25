from lpaapi import *
import argparse
parser = argparse.ArgumentParser(description='Create a LPA via the API')
parser.add_argument('-hw', action='store_true',
                    default=False,
                    help='Choose Health and Welfare')
parser.add_argument('-d', action='store_true',
                    default=False,
                    help='Create Donor')
parser.add_argument('-a', action='store_true',
                    default=False,
                    help='Add attorneys')
parser.add_argument('-r', action='store_true',
                    default=False,
                    help='Add Replacement attorneys')
parser.add_argument('-c', action='store_true',
                    default=False,
                    help='Add Certificate Provider')
parser.add_argument('-pn', action='store_true',
                    default=False,
                    help='Add People To Notify')
parser.add_argument('-i', action='store_true',
                    default=False,
                    help='Set Instructions and Preferences')
parser.add_argument('-w', action='store_true',
                    default=False,
                    help='Set Who is Registering')
args = parser.parse_args()

if args.hw :
    lpaType = 'health-and-welfare'
else:
    lpaType = 'property-and-financial'

lpaId = makeNewLpa()
setLpaType(lpaId, lpaType)
# if we specified donor, or we specified attorneys which implies we need a donor, then set the donor
if args.d or args.a:
    setDonor(lpaId)
    # this sets life-sustaining treatment to true
    setPrimaryAttorneyDecisions(lpaId, lpaType)
if args.a :
    addPrimaryAttorney(lpaId)
    addSecondPrimaryAttorney(lpaId, lpaType)
    # this keeps life-sustaining treatment set to true and allows attorneys to act jointly
    setPrimaryAttorneyDecisionsMultipleAttorneys(lpaId, lpaType)
if args.r :
    addReplacementAttorney(lpaId)
    addSecondReplacementAttorney(lpaId)
    setReplacementAttorneyDecisionsMultipleAttorneys(lpaId, lpaType)
    setReplacementAttorneysConfirmed(lpaId)
if args.c :
    setCertificateProvider(lpaId)
if args.pn :
    addPersonToNotify(lpaId)
if args.i :
    setInstruction(lpaId)
    setPreference(lpaId)
if args.w :
    setWhoIsRegistering(lpaId)
print(lpaId)
