#!/usr/bin/env bash
set -e
echo "Deploying Charles Gala Venue Production PHP + MySQL Site to https://github.com/delson93/CGV-Website.git..."
git init
git branch -M main
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/delson93/CGV-Website.git
git add .
git commit -m "feat: Production MySQL Charles Gala Venue Website, Bcrypt Admin Auth & Icon-Only Call/WhatsApp Privacy"
git push -u origin main
