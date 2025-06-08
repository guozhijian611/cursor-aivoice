# AI Audio Processing Platform

A high-performance audio/video processing system built with WebMan + ThinkPHP, designed for AI-powered audio extraction, noise reduction, speech recognition, and transcription.

## Features

- **Multi-format Support**: Process both audio and video files
- **Smart Queue System**: Priority-based processing with RabbitMQ
- **Real-time Progress Tracking**: WebSocket-based live updates
- **Scalable Architecture**: Domain-driven design with event sourcing
- **Automatic Retry**: Failed tasks are automatically retried
- **RESTful API**: Complete API for task management

## Architecture

The system follows Domain-Driven Design (DDD) principles:

```
webman/
├── app/
│   ├── domain/          # Domain entities and value objects
│   ├── application/     # Application services and use cases
│   ├── infrastructure/  # External service integrations
│   ├── controller/      # HTTP/WebSocket controllers
│   └── process/         # Background processes
├── config/              # Configuration files
├── database/            # Database migrations
└── storage/             # File storage
```

## Requirements

- PHP 8.2+
- MySQL 5.7+
- Redis 6.0+
- RabbitMQ 3.8+
- Composer

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd <project-directory>
```

2. Navigate to the WebMan directory:
```bash
cd webman
```

3. Install dependencies:
```bash
composer install
```

4. Copy environment configuration:
```bash
cp .env.example .env
```

5. Configure your environment variables in `.env`

6. Run database migrations:
```bash
php database/migrate.php
```

7. Create storage directory:
```bash
mkdir -p storage/uploads
chmod 755 storage/uploads
```

## Usage

### Starting the Server

```bash
php start.php start
```

For daemon mode:
```bash
php start.php start -d
```

### API Endpoints

#### Create Task
```bash
POST /api/tasks
Content-Type: multipart/form-data

Parameters:
- process_type: audio_extract|denoise|fast_recognition|transcription|full_process
- user_id: integer
- files[]: audio/video files
```

#### Get Task Details
```bash
GET /api/tasks/{taskNumber}?user_id={userId}
```

#### List User Tasks
```bash
GET /api/tasks?user_id={userId}&page={page}&page_size={pageSize}
```

#### Cancel Task
```bash
POST /api/tasks/{taskNumber}/cancel
Content-Type: application/json

{
    "user_id": 1
}
```

#### Retry Failed Task
```bash
POST /api/tasks/{taskNumber}/retry
Content-Type: application/json

{
    "user_id": 1
}
```

#### Get Statistics
```bash
GET /api/tasks/statistics?user_id={userId}
```

### WebSocket Progress Tracking

Connect to WebSocket endpoint:
```
ws://localhost:8787/ws
```

Message format:
```json
// Subscribe to task progress
{
    "action": "subscribe",
    "task_id": 123
}

// Unsubscribe from task
{
    "action": "unsubscribe",
    "task_id": 123
}

// Get current progress
{
    "action": "get_progress",
    "task_id": 123
}
```

## Process Types

1. **audio_extract** (Priority: 8) - Extract audio from video files
2. **denoise** (Priority: 6) - Remove background noise from audio
3. **fast_recognition** (Priority: 4) - Quick speech recognition
4. **transcription** (Priority: 2) - Full transcription with timestamps
5. **full_process** (Priority: 2) - Complete workflow from extraction to transcription

## Queue System

The system uses RabbitMQ with four main queues:
- `audio_extract` - For audio extraction jobs
- `denoise` - For noise reduction jobs
- `fast_recognition` - For speech recognition jobs
- `transcription` - For transcription jobs

Each queue has a corresponding dead letter queue for failed messages.

## Background Processes

### TaskScheduler
- Checks pending tasks every 30 seconds
- Retries failed tasks every 5 minutes
- Automatically dispatches tasks to appropriate queues

## Database Schema

### tasks
- Main task information including status, progress, and metadata

### task_files
- Individual file tracking within tasks

### processing_results
- Stores processing outputs and results

### domain_events
- Event sourcing for audit trail and debugging

## Development

### Running Tests
```bash
./vendor/bin/phpunit
```

### Code Style
```bash
./vendor/bin/php-cs-fixer fix
```

## Monitoring

### Health Check
```bash
GET /api/health
```

### Logs
- Application logs: `runtime/logs/`
- Worker logs: Check console output or daemon logs

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL is running
   - Verify credentials in `.env`
   - Ensure database exists

2. **RabbitMQ Connection Failed**
   - Check RabbitMQ is running
   - Verify credentials and vhost
   - Ensure user has proper permissions

3. **File Upload Failed**
   - Check `storage/uploads` directory exists and is writable
   - Verify file size limits in PHP configuration
   - Ensure allowed file types match your requirements

## License

This project is proprietary software. All rights reserved.

## Support

For issues and questions, please contact the development team.
