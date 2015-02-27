BROWSERIFY=./node_modules/.bin/browserify
WATCHIFY=./node_modules/.bin/watchify
BROWSERIFY_FLAGS=-t hbsfy
WATCHIFY_FLAGS=-t hbsfy
UGLIFY=./node_modules/.bin/uglifyjs
UGLIFY_FLAGS=-mc sequences,properties,dead_code,drop_debugger=false,conditionals,evaluate,booleans,loops,if_return,join_vars,drop_console=false,unused=false

dist:
	$(BROWSERIFY) $(BROWSERIFY_FLAGS) root/js/main.js | $(UGLIFY) $(UGLIFY_FLAGS) > root/js/all.min.js
	$(BROWSERIFY) $(BROWSERIFY_FLAGS) root/js/room_overview.js | $(UGLIFY) $(UGLIFY_FLAGS) > root/js/room_overview_bundle.js

watch:
	$(WATCHIFY) $(WATCHIFY_FLAGS) root/js/main.js -o root/js/all.min.js --verbose &
	$(WATCHIFY) $(WATCHIFY_FLAGS) root/js/room_overview.js -o root/js/room_overview_bundle.js --verbose &
	bin/watch_css php &
