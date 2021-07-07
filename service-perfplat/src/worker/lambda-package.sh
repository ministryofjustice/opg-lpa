# Absolute path to zip file
TARGET_ZIP="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"

CURRENT_DIR=`pwd`

if [ ! -d build ] ; then
    mkdir build
fi

# Install dependencies to build directory
pip install --upgrade --target ./build -r requirements.txt

# Include deps in zip file
cd ./build
zip -r $TARGET_ZIP .

# Include the Python module in the zip file
cd $CURRENT_DIR
zip -g -r $TARGET_ZIP perfplatworker