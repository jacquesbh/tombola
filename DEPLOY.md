# Deployment Guide - Tombola AI

This guide explains how to deploy the Tombola real-time raffle application to Clever Cloud using FrankenPHP with Mercure support.

## Overview

- **Platform**: Clever Cloud
- **Runtime**: FrankenPHP (PHP 8.4)
- **Region**: Paris (par)
- **Instance Type**: XS (single instance required for filesystem cache)
- **Real-time**: Mercure (built into FrankenPHP)
- **Frontend**: Tailwind CSS v4 + Stimulus + Turbo

## Prerequisites

1. Clever Cloud account
2. Clever Cloud CLI installed (`npm install -g clever-cloud`)
3. Git repository initialized
4. Yarn installed locally for development

## Initial Setup

### 1. Create the Application

```bash
clever create \
  --type frankenphp \
  --region par \
  --org <your-org-id> \
  tombola-ai
```

Note the generated `app_id` (format: `app_xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`).

### 2. Link to Your Git Repository

```bash
clever link <app_id>
```

Or if already created:
```bash
clever link --org <org-id> tombola-ai
```

## Environment Variables Configuration

Configure all environment variables **before** first deployment:

### PHP & Application Settings

```bash
# PHP version
clever env set CC_PHP_VERSION "8.4"

# Web root directory
clever env set CC_WEBROOT "/public"

# Disable dev dependencies in production
clever env set CC_PHP_DEV_DEPENDENCIES "false"

# Symfony environment
clever env set APP_ENV "prod"

# Application secret (generate with: php -r "echo bin2hex(random_bytes(32));")
clever env set APP_SECRET "<your-generated-secret>"
```

### Mercure Configuration

Generate a secure JWT secret:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

Then configure Mercure variables (use the **same secret** for all three):

```bash
# Generate your secret first, then use it for all three variables
MERCURE_SECRET="<your-generated-jwt-secret>"

clever env set MERCURE_JWT_SECRET "$MERCURE_SECRET"
clever env set MERCURE_PUBLISHER_JWT_KEY "$MERCURE_SECRET"
clever env set MERCURE_SUBSCRIBER_JWT_KEY "$MERCURE_SECRET"

# Mercure URLs (replace app_id with your actual app ID)
clever env set MERCURE_URL "https://app-<your-app-id>.cleverapps.io/.well-known/mercure"
clever env set MERCURE_PUBLIC_URL "https://app-<your-app-id>.cleverapps.io/.well-known/mercure"
```

### Build Hook

This hook runs after dependencies installation:

```bash
clever env set CC_POST_BUILD_HOOK "yarn install --frozen-lockfile && yarn build:css && php bin/console importmap:install && mkdir -p var/cache/prod var/log && chmod -R 777 var && php bin/console asset-map:compile && php bin/console cache:warmup --env=prod"
```

**Build steps explained:**
1. `yarn install --frozen-lockfile` - Install Node dependencies
2. `yarn build:css` - Compile Tailwind CSS v4
3. `php bin/console importmap:install` - Download Stimulus/Turbo assets from CDN
4. `mkdir -p var/cache/prod var/log` - Create cache/log directories
5. `chmod -R 777 var` - Set proper permissions
6. `php bin/console asset-map:compile` - Compile Symfony AssetMapper
7. `php bin/console cache:warmup --env=prod` - Warm up Symfony cache

### Runtime Command

FrankenPHP run command with Mercure enabled:

```bash
clever env set CC_RUN_COMMAND "frankenphp php-server --listen 0.0.0.0:8080 --root /home/bas/app_<your-app-id>/public --mercure"
```

**Important notes:**
- Replace `<your-app-id>` with your actual Clever Cloud app ID
- `--mercure` flag enables the built-in Mercure hub
- `--root` must use the **absolute path** on Clever Cloud filesystem
- Format: `/home/bas/app_<your-app-id>/public`

## Scaling Configuration

**Critical**: This application **must run on a single instance** due to filesystem-based cache.

```bash
# Force single instance mode
clever scale --min-instances 1 --max-instances 1
```

## Deployment

### First Deployment

```bash
git push clever master
```

Or using CLI:
```bash
clever deploy
```

### Monitoring Deployment

Check deployment status:
```bash
clever activity
```

View application logs:
```bash
clever logs --since 5m
```

Check application status:
```bash
clever status
```

## Verification

### 1. Check Application is Running

```bash
curl -I https://app-<your-app-id>.cleverapps.io/
# Should return: HTTP/2 302 (redirect to join page)
```

### 2. Verify Mercure Endpoint

```bash
curl -I https://app-<your-app-id>.cleverapps.io/.well-known/mercure
# Should return: HTTP/2 401 (requires authentication - this is correct)
```

### 3. Test the Application

1. Open `https://app-<your-app-id>.cleverapps.io/board/create` to create a game board
2. Note the board code generated
3. Open the join URL with the board code
4. Verify real-time updates between board and player screens

## Troubleshooting

### Issue: Assets Not Loading (404 on /assets/vendor/)

**Cause**: `importmap:install` not running during build

**Solution**: Verify `CC_POST_BUILD_HOOK` includes `php bin/console importmap:install`

```bash
clever env | grep CC_POST_BUILD_HOOK
```

### Issue: Mercure Not Working (No Real-time Updates)

**Causes & Solutions**:

1. **Missing `--mercure` flag**
   ```bash
   # Verify run command includes --mercure
   clever env | grep CC_RUN_COMMAND
   ```

2. **Missing or invalid JWT keys**
   ```bash
   # Verify all three Mercure env vars are set with the same secret
   clever env | grep MERCURE_
   ```

3. **Wrong Mercure URLs**
   ```bash
   # Verify URLs point to your app domain
   clever env | grep MERCURE_URL
   ```

### Issue: Build Fails During Tailwind Compilation

**Cause**: Missing `yarn build:css` in build hook

**Solution**: Ensure build hook includes `yarn build:css` step

### Issue: 500 Error on Production

**Causes**:
1. Cache/log directories not writable
2. Cache not warmed up
3. Wrong `APP_ENV` value

**Solutions**:
```bash
# Verify environment
clever env | grep APP_ENV
# Should be: APP_ENV="prod"

# Check build hook includes cache warmup
clever env | grep CC_POST_BUILD_HOOK
# Should include: php bin/console cache:warmup --env=prod
```

### Issue: Multiple Instances Running

**Cause**: Auto-scaling enabled

**Solution**: Force single instance mode
```bash
clever scale --min-instances 1 --max-instances 1
```

### Viewing Detailed Logs

For build failures:
```bash
clever logs --deployment-id <deployment-id>
```

Get deployment ID from:
```bash
clever activity
```

## Environment Variables Reference

| Variable | Value | Description |
|----------|-------|-------------|
| `CC_PHP_VERSION` | `8.4` | PHP version to use |
| `CC_WEBROOT` | `/public` | Public directory relative path |
| `CC_PHP_DEV_DEPENDENCIES` | `false` | Skip dev dependencies in production |
| `APP_ENV` | `prod` | Symfony environment |
| `APP_SECRET` | `<random-32-bytes-hex>` | Symfony application secret |
| `MERCURE_JWT_SECRET` | `<random-32-bytes-hex>` | Mercure JWT signing key |
| `MERCURE_PUBLISHER_JWT_KEY` | `<same-as-jwt-secret>` | Key for publishing to Mercure |
| `MERCURE_SUBSCRIBER_JWT_KEY` | `<same-as-jwt-secret>` | Key for subscribing to Mercure |
| `MERCURE_URL` | `https://app-<id>.cleverapps.io/.well-known/mercure` | Internal Mercure hub URL |
| `MERCURE_PUBLIC_URL` | `https://app-<id>.cleverapps.io/.well-known/mercure` | Public Mercure hub URL |
| `CC_POST_BUILD_HOOK` | See build hook section above | Commands to run after composer install |
| `CC_RUN_COMMAND` | See runtime command section above | FrankenPHP startup command |

## Key Technical Decisions

### Why FrankenPHP?

1. **Built-in Mercure**: No separate service needed
2. **HTTP/2 & HTTP/3**: Better real-time performance
3. **Better Performance**: Compiled with Go, faster than traditional PHP-FPM
4. **Modern**: Designed for modern Symfony applications

### Why Single Instance?

- **Filesystem Cache**: Symfony cache uses filesystem by default
- **Shared State**: Single instance ensures cache coherency
- **Simplicity**: No need for Redis/Memcached for a simple raffle app

### Why `importmap:install` in Build Hook?

- **No Vendor Assets in Git**: Keeps repository clean
- **CDN Download**: Assets downloaded from jsDelivr during build
- **Symfony Native**: Uses Symfony's built-in asset management

## Common Commands

```bash
# View all environment variables
clever env

# Update an environment variable
clever env set <KEY> "<value>"

# View application logs
clever logs --since 10m

# Restart application
clever restart

# Open application in browser
clever open

# View application info
clever status

# SSH into application (for debugging)
clever ssh
```

## Local Development

For local development, you don't need FrankenPHP. Use Symfony CLI:

```bash
# Install dependencies
composer install
yarn install

# Build assets
yarn build:css
php bin/console importmap:install
php bin/console asset-map:compile

# Start local server with Mercure
symfony server:start

# In another terminal, start Mercure hub
symfony run -d --watch=config,src,templates,vendor/symfony/mercure-bundle/src/Resources/config --watch=.env
```

Or use the built-in PHP server:
```bash
php -S localhost:8000 -t public/
```

## Production Checklist

Before deploying to production:

- [ ] All environment variables configured
- [ ] `APP_SECRET` is a strong random value
- [ ] Mercure JWT secrets are strong random values
- [ ] All Mercure URLs point to correct domain
- [ ] Single instance scaling configured
- [ ] Build hook includes all necessary steps
- [ ] Run command includes `--mercure` flag
- [ ] Git repository is clean (no vendor assets committed)

## Support

- Clever Cloud Documentation: https://www.clever-cloud.com/doc/
- FrankenPHP Documentation: https://frankenphp.dev/
- Symfony Documentation: https://symfony.com/doc/current/
- Mercure Documentation: https://symfony.com/doc/current/mercure.html

## Updates & Maintenance

To update the application:

1. Make changes locally
2. Test locally with `symfony server:start`
3. Commit changes: `git commit -m "Your message"`
4. Deploy: `git push clever master` or `clever deploy`
5. Monitor: `clever logs --since 1m`

To update dependencies:

```bash
# Update composer dependencies
composer update

# Update yarn dependencies  
yarn upgrade

# Commit lockfiles
git add composer.lock yarn.lock
git commit -m "Update dependencies"
git push clever master
```
