#!/bin/bash

# Laravel Bref WebSocket Package Deployment Script
# This script prepares and publishes the package to Composer

set -e

echo "🚀 Deploying Laravel Bref WebSocket Package..."

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from the package root directory."
    exit 1
fi

# Check if git is available
if ! command -v git &> /dev/null; then
    echo "❌ Error: git is not installed or not in PATH"
    exit 1
fi

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo "❌ Error: composer is not installed or not in PATH"
    exit 1
fi

echo "📦 Preparing package..."

# Clean up any previous builds
rm -rf vendor/
rm -f composer.lock

# Install dependencies
echo "📥 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Run tests if they exist
if [ -d "tests" ] && [ -f "phpunit.xml" ]; then
    echo "🧪 Running tests..."
    composer test
fi

# Check if git repository is clean
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Warning: Git repository has uncommitted changes"
    echo "   Current changes:"
    git status --porcelain
    echo ""
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Deployment cancelled"
        exit 1
    fi
fi

# Get current version
CURRENT_VERSION=$(composer show --format=json | jq -r '.version' 2>/dev/null || echo "dev-master")
echo "📋 Current version: $CURRENT_VERSION"

# Create git tag if this is a new version
if [ "$CURRENT_VERSION" = "dev-master" ]; then
    echo "🏷️  Creating git tag for version 1.0.0..."
    git tag -a v1.0.0 -m "Release version 1.0.0"
    git push origin v1.0.0
fi

echo "✅ Package prepared successfully!"
echo ""
echo "📋 Next steps to publish to Packagist:"
echo "1. Push your code to GitHub:"
echo "   git push origin main"
echo ""
echo "2. Go to https://packagist.org/packages/submit"
echo "3. Submit your GitHub repository URL"
echo "4. Wait for approval and indexing"
echo ""
echo "5. Once published, users can install with:"
echo "   composer require laravel-bref/websocket"
echo ""
echo "🎉 Package deployment preparation complete!"
