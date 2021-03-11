from lpaapi import *
import argparse
parser = argparse.ArgumentParser(description='Create a LPA via the API')
parser.add_argument('-hw', action='store_true',
                    default=False,
                    help='Choose Health and Welfare')
parser.add_argument('-d', action='store_true',
                    default=False,
                    help='Create Donor')
args = parser.parse_args()

if args.hw :
    lpaType = 'health-and-welfare'
else:
    lpaType = 'property-and-financial'

lpaId = makeNewLpa()
setLpaType(lpaId, lpaType)
if args.d :
    setDonor(lpaId)
setPrimaryAttorneyDecisions(lpaId)
print(lpaId)
