# Script to package Python worker and its dependencies
# for deployment as an AWS lambda

# Usage: worker-package.sh <path to output zip file>

# Absolute path to output zip file
TARGET_ZIP="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"

CURRENT_DIR=`pwd`

if [ ! -d build ] ; then
    mkdir build
fi

# Install dependencies to build directory
pip install --upgrade --target ./build -r worker-requirements.txt

# Include deps in zip file
cd ./build
zip -r $TARGET_ZIP .

# Include the Python module in the zip file
cd $CURRENT_DIR
zip -g --exclude \*.DS_Store -r $TARGET_ZIP perfplatworker
