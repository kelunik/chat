echo "Running javascript tests..."
echo ""
echo "npm -v"
npm -v
echo ""
echo "node -v"
node -v
echo ""

cd root/js
sudo npm install
sudo npm install -g hbsfy handlebars mocha

npm run build

cd test
../../../travis/env.sh mocha
