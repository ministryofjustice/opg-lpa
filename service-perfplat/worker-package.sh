# Script to package Python worker and its dependencies
# for deployment as an AWS lambda

# Usage: worker-package.sh <path to output zip file>

# Absolute path to output zip file
TARGET_ZIP="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"

CURRENT_DIR=`pwd`

if [ ! -d build/packages ] ; then
    mkdir -p build/packages
fi

# Install dependencies to build directory
pip3 install --upgrade --target ./build/packages -r worker-requirements.txt

cd ./build

# Time to get funky: get a statically-compiled psycopg2 and put it into the zip file
# see https://github.com/jkehler/awslambda-psycopg2
if [ ! -d ./packages/psycopg2 ] ; then
    git clone https://github.com/jkehler/awslambda-psycopg2.git
    cp -a awslambda-psycopg2/psycopg2-3.8 ./packages/psycopg2
fi

# Include deps in zip file
cd ./packages
zip -r $TARGET_ZIP .

# Include the Python modules in the zip file
cd $CURRENT_DIR
zip -g --exclude \*.DS_Store -r $TARGET_ZIP perfplatworker
zip -g --exclude \*.DS_Store -r $TARGET_ZIP perfplatcommon