minify-js = closure-compiler --jscomp_off misplacedTypeAnnotation

#all: desktop/js/jMQTT.min.js

%.min.js: %.js
	$(minify-js) --js $< --js_output_file $@

chmod:
	find . -type f -exec chmod 664 {} \;
	#chmod 774 resources/install_apt.sh
