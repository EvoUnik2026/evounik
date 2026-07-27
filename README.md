# Evounik - Symfony 6.4 + MariaDB Application

A modern Symfony 6.4 application with MariaDB database, running on Docker containers.

## Prerequisites

- Docker and Docker Compose installed on your system
- Git (for cloning the repository)

## Project Structure

```
evounik/
├── docker/
│   ├── nginx/
│   │   └── default.conf          # Nginx configuration
│   └── php/
│       ├── Dockerfile            # PHP-FPM Docker image
│       └── entrypoint.sh         # PHP container startup script
├── config/
│   └── packages/
│       ├── doctrine.yaml         # Doctrine ORM configuration
│       ├── framework.yaml        # Framework configuration
│       ├── security.yaml         # Security configuration
│       └── twig.yaml             # Twig templating configuration
├── public/
│   └── index.php                 # Application entry point
├── src/                          # Application source code
├── templates/                    # Twig templates
├── var/                          # Cache, logs, sessions
├── .env                          # Environment variables
├── .gitignore                    # Git ignore rules
├── composer.json                 # PHP dependencies
└── docker-compose.yml            # Docker services configuration
```

## Quick Start

### 1. Clone the Repository

```bash
git clone <repository-url> evounik
cd evounik
```

### 2. Start Docker Containers

```bash
docker-compose up -d
```

This will start the following services:
- **MariaDB** - Database server (port 3306)
- **PHP-FPM** - PHP application server (port 9000)
- **Nginx** - Web server (ports 80, 443)
- **Redis** - Cache server (port 6379)

### 3. Install Symfony Dependencies

```bash
docker-compose exec php composer install
```

### 4. Create Database

```bash
docker-compose exec php php bin/console doctrine:database:create
```

### 5. Run Database Migrations

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Access the Application

Open your browser and navigate to:
- **HTTP**: http://localhost
- **HTTPS**: https://localhost (requires SSL certificates)

## Docker Commands

### Start Services

```bash
docker-compose up -d
```

### Stop Services

```bash
docker-compose stop
```

### Restart Services

```bash
docker-compose restart
```

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mariadb
```

### Execute Commands in PHP Container

```bash
docker-compose exec php <command>

# Examples:
docker-compose exec php composer install
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console make:controller
```

### Access MariaDB

```bash
docker-compose exec mariadb mysql -u evounik_user -pevounik_password evounik_db
```

Or from your host machine:
```bash
mysql -h 127.0.0.1 -P 3306 -u evounik_user -pevounik_password evounik_db
```

### Access Redis

```bash
docker-compose exec redis redis-cli
```

## Environment Variables

The application uses the following environment variables (defined in `.env`):

| Variable | Description | Default Value |
|----------|-------------|---------------|
| `APP_ENV` | Application environment | `dev` |
| `APP_DEBUG` | Enable debug mode | `1` (true in dev) |
| `APP_SECRET` | Application secret key | Random string |
| `DATABASE_URL` | Database connection string | `mysql://evounik_user:evounik_password@mariadb:3306/evounik_db` |
| `REDIS_URL` | Redis connection string | `redis://redis:6379` |

### Database Credentials

- **Host**: mariadb (or localhost from host machine)
- **Port**: 3306
- **Database**: evounik_db
- **Username**: evounik_user
- **Password**: evounik_password
- **Root Password**: root_password

## Development

### Clear Cache

```bash
docker-compose exec php php bin/console cache:clear
```

### Create a New Controller

```bash
docker-compose exec php php bin/console make:controller
```

### Create a New Entity

```bash
docker-compose exec php php bin/console make:entity
```

### Generate Migrations

```bash
docker-compose exec php php bin/console doctrine:migrations:diff
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Run Tests

```bash
docker-compose exec php php bin/phpunit
```

## Symfony Console Commands

Access the Symfony console:

```bash
docker-compose exec php php bin/console
```

Common commands:
- `cache:clear` - Clear the cache
- `cache:warmup` - Warm up the cache
- `make:migration` - Create a new migration
- `migrations:migrate` - Execute migrations
- `make:controller` - Create a new controller
- `make:entity` - Create a new entity
- `make:form` - Create a new form

## Troubleshooting

### Permission Issues

If you encounter permission issues with the `var/` directory:

```bash
docker-compose exec php chown -R www-data:www-data /var/www/html/var
```

### Database Connection Issues

Ensure MariaDB is ready before running migrations:

```bash
docker-compose exec php php bin/console doctrine:database:create
```

### Clear All Containers and Volumes

To start fresh (WARNING: This will delete all data):

```bash
docker-compose down -v
docker-compose up -d
```

### Rebuild PHP Container

If you make changes to the Dockerfile:

```bash
docker-compose build php
docker-compose up -d
```

## Production Deployment

For production deployment:

1. Set `APP_ENV=prod` and `APP_DEBUG=0` in `.env`
2. Install dependencies without dev packages:
   ```bash
   docker-compose exec php composer install --no-dev --optimize-autoloader
   ```
3. Warm up the cache:
   ```bash
   docker-compose exec php php bin/console cache:clear
   docker-compose exec php php bin/console cache:warmup
   ```
4. Run migrations:
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

## Security Notes

- Change default passwords in production
- Use strong `APP_SECRET` in production
- Configure SSL certificates for HTTPS
- Restrict database access to application container only
- Use environment-specific `.env.local` files for sensitive data

## Additional Resources

- [Symfony Documentation](https://symfony.com/doc/6.4/index.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm/en/current/reference/index.html)
- [Docker Documentation](https://docs.docker.com/)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)

## License

MIT License