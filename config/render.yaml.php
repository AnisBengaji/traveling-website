services:
  - type: web
    name: symfony-app
    env: php
    repo: https://github.com/AnisBengaji/traveling-website
    branch: main
    plan: free
    buildCommand: |
      composer install --no-dev --optimize-autoloader
    startCommand: |
      php bin/console doctrine:migrations:migrate --no-interaction
      symfony serve --no-tls --port $PORT
    envVars:
      - key: APP_ENV
        value: prod
      - key: DATABASE_URL
        fromDatabase:
          name: trippin-db
          property: connectionString
