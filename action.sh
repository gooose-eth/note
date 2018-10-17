#!/bin/bash

# set port
[[ -z "$2" ]] && port=8000 || port=$2

# func / start server
start() {
	php -S 0.0.0.0:$port -t ./
}

case "$1" in
	start)
		start
		;;

	setup)
		# make cache directory
		if [ ! -d cache ]; then
			mkdir cache
			chmod 777 cache
		fi
		# copy .env
		if [ ! -f .env ]; then
			cp .env.example .env
		fi
		;;

	watch)
		parcel watch assets/js/app.js --global Redgoose --no-autoinstall --out-dir assets/dist --cache-dir cache/parcel
		;;

	build)
		rm -rf assets/dist
		parcel build assets/js/app.js --global Redgoose --no-source-maps --out-dir assets/dist --cache-dir cache/parcel
		;;

	*)
		echo "Usage: ./action.sh {start|setup|watch|build}" >&2
		exit 3
		;;
esac