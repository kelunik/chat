# Add local node_modules bin to the path for this command
export PATH="../../../root/js/node_modules/.bin:$PATH"

# execute the rest of the command
exec "$@"
