BROWSERIFY=./node_modules/.bin/browserify
FLAGS=-d -t hbsfy
DIST_FLAGS=-t hbsfy
UGLIFY=./node_modules/.bin/uglifyjs
UGLIFY_FLAGS=-mc sequences,properties,dead_code,drop_debugger=false,conditionals,evaluate,booleans,loops,if_return,join_vars,drop_console=false,unused=false

all: chat.js \
     room_overview.js

dist:
	$(BROWSERIFY) $(DIST_FLAGS) root/js/main.js | $(UGLIFY) $(UGLIFY_FLAGS) > root/js/all.min.js
	$(BROWSERIFY) $(DIST_FLAGS) root/js/room_overview.js | $(UGLIFY) $(UGLIFY_FLAGS) > root/js/room_overview_bundle.js

chat.js: $(BACKGROUND_FILES)
	$(BROWSERIFY) $(FLAGS) root/js/main.js > root/js/all.min.js

room_overview.js: $(POPUP_FILES)
	$(BROWSERIFY) $(FLAGS) root/js/room_overview.js > root/js/room_overview_bundle.js
