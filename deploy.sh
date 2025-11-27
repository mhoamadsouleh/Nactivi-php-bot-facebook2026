#!/bin/bash

echo "🚀 Deploying Facebook Djezzy Bot to Render..."

# Check if all required files exist
required_files=("index.php" "composer.json" "render.yaml" ".gitignore")
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Missing required file: $file"
        exit 1
    fi
done

echo "✅ All required files present"
echo "📦 Ready for deployment to Render!"

# Instructions
echo ""
echo "📋 Deployment Instructions:"
echo "1. Push these files to GitHub"
echo "2. Go to https://render.com"
echo "3. Create new Web Service"
echo "4. Connect your GitHub repository"
echo "5. Use these settings:"
echo "   - Environment: PHP"
echo "   - Build Command: (leave empty)"
echo "   - Start Command: php -S 0.0.0.0:10000 index.php"
echo "6. Deploy!"