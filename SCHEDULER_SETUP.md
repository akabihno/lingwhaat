# Wiktionary Articles Parser Scheduler

This document describes the automated scheduler setup for parsing Wiktionary articles using Symfony Scheduler and Messenger components.

## Overview

The scheduler automatically runs the Wiktionary IPA parser for configured languages at regular intervals using Symfony's built-in scheduling and message queue system.

## Architecture

- **Symfony Scheduler**: Manages recurring task schedules
- **Symfony Messenger**: Handles async message processing
- **Redis**: Used for message transport and distributed locking
- **Docker**: Runs dedicated worker containers for scheduling and message processing

## Components

### 1. Command
`src/Command/ParseWiktionaryArticlesCommand.php`
- Symfony console command for manual execution
- Usage: `php bin/console app:parse-wiktionary-articles <language> --limit=<number>`

### 2. Schedule Provider
`src/Scheduler/WiktionaryParserScheduleProvider.php`
- Defines when tasks should run
- Currently configured for:
  - Dutch: Every 6 minutes, 300 articles per batch
  - Icelandic: Every 6 minutes, 300 articles per batch
  - Includes 30-second jitter to prevent timing conflicts

### 3. Message & Handler
- `src/Message/ParseWiktionaryArticlesMessage.php`: Message DTO
- `src/MessageHandler/ParseWiktionaryArticlesMessageHandler.php`: Processes messages asynchronously

### 4. Docker Services
Two dedicated worker containers:
- **scheduler-worker**: Consumes scheduled messages (256MB RAM limit)
- **messenger-worker**: Processes async parsing jobs (512MB RAM limit)

## Configuration

### Environment Variables (.env)
```env
LOCK_DSN=redis://localhost:6379
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
```

### Messenger Config (config/packages/messenger.yaml)
- Defines transports (async, scheduler_default, failed)
- Configures retry strategy (3 retries, exponential backoff)
- Routes messages to appropriate transports

### Scheduler Config (config/packages/scheduler.yaml)
- Uses Redis-based locking to prevent duplicate task execution

## Usage

### Start the Scheduler
```bash
# Start all services including scheduler workers
docker-compose up -d

# View scheduler worker logs
docker logs -f scheduler-worker

# View messenger worker logs
docker logs -f messenger-worker
```

### Manual Command Execution
```bash
# Run parser manually for testing
docker exec php-app php bin/console app:parse-wiktionary-articles dutch --limit=100

# List all available commands
docker exec php-app php bin/console list
```

### Adding New Languages

Edit `src/Scheduler/WiktionaryParserScheduleProvider.php`:

```php
$schedule->add(
    RecurringMessage::every(
        '6 minutes',
        new \App\Message\ParseWiktionaryArticlesMessage('spanish', 300)
    )->withJitter(30)
);
```

### Monitoring

Check Elasticsearch logs for parser activity.

### Failed Messages

Failed messages are stored in the `failed` transport using Doctrine.

```bash
# List failed messages
docker exec php-app php bin/console messenger:failed:show

# Retry failed messages
docker exec php-app php bin/console messenger:failed:retry

# Remove a failed message
docker exec php-app php bin/console messenger:failed:remove <id>
```

## Resource Usage

Total memory allocated for workers:
- scheduler-worker: 256MB
- messenger-worker: 512MB
- **Total: 768MB**

## Troubleshooting

### Scheduler is not running
1. Check if scheduler-worker container is running: `docker ps`
2. Check logs: `docker logs scheduler-worker`
3. Verify Redis is accessible: `docker exec scheduler-worker redis-cli -h localhost ping`

### Messages not being processed
1. Check messenger-worker logs: `docker logs messenger-worker`
2. Verify message transport DSN in .env
3. Check Redis for queued messages: `docker exec redis-cache redis-cli KEYS "*"`

### Duplicate executions
- Ensure LOCK_DSN is properly configured
- Verify Redis is accessible from all worker containers

### Scale Workers
Add more messenger workers by duplicating the service in docker-compose.yml:
```yaml
messenger-2:
  # Same config as messenger
  container_name: messenger-worker-2
```
