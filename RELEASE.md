# Create New Release

    ./bin/make-release.sh <version> [--no-tag]

e.g.

    ./bin/make-release.sh 1.0.0

## Docker Example

    docker run -it -v $(pwd):/tmp/pwd -w /tmp/pwd -v $(realpath ~/.gitconfig):/tmp/user/.gitconfig -e "HOME=/tmp/user" -u $(id -u):$(id -g) dev-docker_web82 bin/make-release.sh 1.0.0 --no-tag
