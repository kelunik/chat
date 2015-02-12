SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`
ROOT=`dirname $SCRIPTPATH`

# Add local node_modules bin to the path for this command
export PATH="$ROOT/node_modules/.bin:$PATH"

# execute the rest of the command
exec "$@"
