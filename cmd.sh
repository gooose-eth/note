script="$(basename "$(test -L "$0" && readlink "$0" || echo "$0")")"

case "$1" in

  build)
    docker buildx build --platform=linux/amd64 -t redgoose/note.redgoose.me:latest .
    ;;

  upload)
    docker save redgoose/note.redgoose.me:latest | ssh -C redgoose.me 'cd ~/docker/note && docker-compose down && docker load &&
    docker-compose up -d && cd ../service && ./cmd.sh service reload'
    ;;

  upgrade)
    docker buildx build --platform=linux/amd64 -t redgoose/note.redgoose.me:latest .
    docker save redgoose/note.redgoose.me:latest | ssh -C redgoose.me 'cd ~/docker/note && docker-compose down && docker load &&
docker-compose up -d && cd ../service && ./cmd.sh service reload'
    ;;

  *)
    echo "Usage: ${script} {build|upload|upgrade}" >&2
    exit 0
    ;;

esac
