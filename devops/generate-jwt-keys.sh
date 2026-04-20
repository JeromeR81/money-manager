#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
JWT_DIR="$SCRIPT_DIR/../backend/config/jwt"
ENV_FILE="$SCRIPT_DIR/../.env"

mkdir -p "$JWT_DIR"

if [ -f "$JWT_DIR/private.pem" ] && [ "${1:-}" != "--force" ]; then
    echo "JWT keys already exist. Use --force to regenerate."
    exit 0
fi

echo "Generating JWT RS256 keys..."

PASSPHRASE=$(openssl rand -base64 32)

openssl genrsa -aes256 -passout pass:"$PASSPHRASE" -out "$JWT_DIR/private.pem" 4096
openssl rsa -pubout -in "$JWT_DIR/private.pem" -passin pass:"$PASSPHRASE" -out "$JWT_DIR/public.pem"

chmod 600 "$JWT_DIR/private.pem"
chmod 644 "$JWT_DIR/public.pem"

if [ -f "$ENV_FILE" ]; then
    if grep -q "^JWT_PASSPHRASE=" "$ENV_FILE"; then
        sed -i "s|^JWT_PASSPHRASE=.*|JWT_PASSPHRASE=$PASSPHRASE|" "$ENV_FILE"
    else
        echo "JWT_PASSPHRASE=$PASSPHRASE" >> "$ENV_FILE"
    fi
else
    cp "$SCRIPT_DIR/../.env.example" "$ENV_FILE"
    sed -i "s|^JWT_PASSPHRASE=.*|JWT_PASSPHRASE=$PASSPHRASE|" "$ENV_FILE"
fi

echo "Done."
echo "  Private : $JWT_DIR/private.pem"
echo "  Public  : $JWT_DIR/public.pem"
echo "  Passphrase written to .env (JWT_PASSPHRASE)"
