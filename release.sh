#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Load .env if present
if [[ -f "$SCRIPT_DIR/.env" ]]; then
    set -a
    source "$SCRIPT_DIR/.env"
    set +a
fi

DOCKER_USERNAME="${DOCKER_USERNAME:?DOCKER_USERNAME must be set in .env or environment}"
DOCKER_IMAGE_NAME="${DOCKER_IMAGE_NAME:-currere-web}"
VERSION="${1:-}"

IMAGE="$DOCKER_USERNAME/$DOCKER_IMAGE_NAME"

if [[ -z "$VERSION" ]]; then
    echo "Usage: ./release.sh <version>"
    echo "Example: ./release.sh 1.0.0"
    exit 1
fi

PLATFORM="${DOCKER_PLATFORM:-linux/amd64}"

echo "Building $IMAGE:$VERSION for $PLATFORM ..."
docker buildx build --platform "$PLATFORM" -t "$IMAGE:$VERSION" -t "$IMAGE:latest" --push .

echo "Released $IMAGE:$VERSION and $IMAGE:latest"
