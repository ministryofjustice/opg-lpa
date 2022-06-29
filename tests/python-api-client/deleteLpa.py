from lpaapi import *
import argparse

parser = argparse.ArgumentParser(description="Delete a LPA via the API, given a LPA ID")
parser.add_argument("-i")
args = parser.parse_args()

if args.i:
    deleteLpa(args.i)
else:
    print("usage: deleteLpa -i 39561005664")
