# Create New Release

    ./bin/make-release.sh <version> [--no-tag]

e.g.

    ./bin/make-release.sh 1.0.0

## Docker Example

    docker run -it --rm -v $(pwd):/tmp/pwd:z -w /tmp/pwd -v $(realpath ~/.gitconfig):/tmp/user/.gitconfig:z -e "HOME=/tmp/user" --userns=keep-id:uid=$(id -u),gid=$(id -g) dev-docker_web82 bin/make-release.sh 1.0.0 --no-tag
