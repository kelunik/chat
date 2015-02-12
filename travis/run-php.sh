echo "Running PHP tests..."
echo ""

wget http://dev.kelunik.com:8080/php/builds/master -q -O php
chmod +x php

./php -v
echo ""
