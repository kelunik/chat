echo "Running javascript tests..."
echo ""
echo "npm -v"
npm -v
echo ""
echo "node -v"
node -v
echo ""

npm install
npm run build 2>&1 | tee js.log

if grep -i error js.log > /dev/null
then
    exit 1
fi

travis/env.sh mocha root/js/test
