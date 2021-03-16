from lpaapi import *
import argparse
parser = argparse.ArgumentParser(description='Create a LPA via the API')
parser.add_argument('-hw', action='store_true',
                    default=False,
                    help='Choose Health and Welfare')
parser.add_argument('-d', action='store_true',
                    default=False,
                    help='Create Donor')
parser.add_argument('-a1', action='store_true',
                    default=False,
                    help='Add 1 attorney')
parser.add_argument('-a2', action='store_true',
                    default=False,
                    help='Add 2 attorneys')
args = parser.parse_args()

if args.hw :
    lpaType = 'health-and-welfare'
else:
    lpaType = 'property-and-financial'

lpaId = makeNewLpa()
print(lpaId)
setLpaType(lpaId, lpaType)
if args.d :
    setDonor(lpaId)
setPrimaryAttorneyDecisions(lpaId)
if args.a1 :
    addPrimaryAttorney(lpaId)
if args.a2 :
    addPrimaryAttorney(lpaId)
    addSecondPrimaryAttorney(lpaId)
