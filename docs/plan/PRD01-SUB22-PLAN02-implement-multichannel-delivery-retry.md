---
plan: Implement Multi-Channel Notification Delivery with Retry Logic
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, notifications, events, multi-channel, email, sms, push, delivery, retry, messaging]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the multi-channel notification delivery system supporting email, SMS, push notifications, in-app messages, and webhooks. It includes channel-specific drivers (SendGrid, Twilio, Firebase FCM), delivery retry logic with exponential backoff, Laravel Queue/Horizon integration for async processing, and comprehensive delivery status tracking.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-NE-001**: Support multi-channel notifications (email, SMS, push, in-app, webhook)
- **FR-NE-005**: Track notification delivery status (sent, delivered, failed, bounced)
- **BR-NE-003**: Failed notifications must retry with exponential backoff up to 3 attempts
- **PR-NE-001**: Notifications queued and delivered within 3 seconds of triggering event
- **PR-NE-002**: Support 10,000+ notifications per minute during peak load
- **IR-NE-002**: Support external notification services (SendGrid, Twilio, Firebase)

**Security Requirements:**
- **SEC-001**: Validate API keys for external services
- **SEC-002**: Encrypt sensitive delivery data (phone numbers, device tokens) at rest
- **SEC-003**: Rate limiting on notification sending per tenant

**Architectural Constraints:**
- **ARCH-NE-001**: Use Redis with Laravel Queue/Horizon for async processing
- **ARCH-001**: Contract-driven service design
- **ARCH-002**: Repository pattern for delivery log persistence
- **ARCH-003**: Event-driven architecture for delivery status updates

**Performance Constraints:**
- **PERF-001**: Notification delivery within 3 seconds (PR-NE-001)
- **PERF-002**: Process 10,000+ notifications per minute (PR-NE-002)
- **PERF-003**: Retry delays: 1st retry +30s, 2nd retry +120s, 3rd retry +300s (exponential backoff)

**Guidelines:**
- **GUD-001**: Follow Laravel 12 queue conventions
- **GUD-002**: Use PHP 8.2+ features
- **GUD-003**: All code must pass Pint formatting
- **GUD-004**: Minimum 80% test coverage

**Patterns:**
- **PAT-001**: Strategy pattern for channel-specific drivers
- **PAT-002**: Queue jobs for async delivery
- **PAT-003**: Event broadcasting for delivery status updates
- **PAT-004**: Circuit breaker pattern for external service failures

## 2. Implementation Steps

### GOAL-001: Create Notification Service with Multi-Channel Support

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-001 | Multi-channel notification delivery | | |
| ARCH-001, PAT-001 | Contract-driven service with strategy pattern | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create NotificationServiceContract interface at `packages/notifications/src/Contracts/NotificationServiceContract.php` with methods: send(array $data, string $channel): NotificationLog, sendBatch(array $notifications): Collection, queueNotification(array $data, string $channel, ?Carbon $scheduledAt = null): string (returns job ID), getDeliveryStatus(string $externalId): string | | |
| TASK-002 | Create NotificationChannelDriverContract interface at `packages/notifications/src/Contracts/NotificationChannelDriverContract.php` with methods: send(array $data): array (returns ['success' => bool, 'external_id' => string, 'error' => ?string]), validate(array $data): bool, getChannelType(): string | | |
| TASK-003 | Create NotificationService at `packages/notifications/src/Services/NotificationService.php` implementing NotificationServiceContract, constructor injecting NotificationLogRepositoryContract and array of channel drivers, send() method selecting driver based on channel type, creating NotificationLog entry with status 'pending', dispatching SendNotificationJob to queue, updating log with 'queued' status | | |
| TASK-004 | Add sendBatch() method to NotificationService accepting array of notification data, creating NotificationLog entries in bulk using DB transaction, dispatching multiple SendNotificationJob instances using Bus::batch(), returning Collection of created logs | | |
| TASK-005 | Add queueNotification() method accepting scheduledAt parameter, creating NotificationLog with scheduled_at timestamp, dispatching SendNotificationJob with delay() if scheduled for future, returning unique job ID for tracking | | |
| TASK-006 | Create enum NotificationChannel at `packages/notifications/src/Enums/NotificationChannel.php` with cases: EMAIL, SMS, PUSH, IN_APP, WEBHOOK, add values() static method returning array of case values | | |
| TASK-007 | Create enum NotificationStatus at `packages/notifications/src/Enums/NotificationStatus.php` with cases: PENDING, QUEUED, SENT, DELIVERED, FAILED, BOUNCED, add isSuccess() method returning true for SENT/DELIVERED | | |
| TASK-008 | Create enum NotificationPriority at `packages/notifications/src/Enums/NotificationPriority.php` with cases: CRITICAL, HIGH, NORMAL, LOW, add queueName() method returning 'notifications-critical', 'notifications-high', 'notifications', 'notifications-low' | | |

### GOAL-002: Implement Channel-Specific Drivers for Email, SMS, and Push

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-NE-002 | Integration with SendGrid, Twilio, Firebase FCM | | |
| PAT-001 | Strategy pattern implementation for each channel | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create EmailChannelDriver at `packages/notifications/src/Services/Channels/EmailChannelDriver.php` implementing NotificationChannelDriverContract, constructor accepting SendGrid API client, send() method calling SendGrid API with from, to, subject, html parameters, validate() checking email format and required fields, getChannelType() returning 'email', handle SendGrid response and extract message ID as external_id | | |
| TASK-010 | Create SmsChannelDriver at `packages/notifications/src/Services/Channels/SmsChannelDriver.php` implementing NotificationChannelDriverContract, constructor accepting Twilio client, send() method calling Twilio API with from, to, body parameters, validate() checking phone number format (E.164), getChannelType() returning 'sms', handle Twilio response and extract SID as external_id | | |
| TASK-011 | Create PushChannelDriver at `packages/notifications/src/Services/Channels/PushChannelDriver.php` implementing NotificationChannelDriverContract, constructor accepting Firebase FCM client, send() method calling FCM API with token, notification (title, body), data payload, validate() checking device token format, getChannelType() returning 'push', handle FCM response and extract message ID as external_id | | |
| TASK-012 | Create InAppChannelDriver at `packages/notifications/src/Services/Channels/InAppChannelDriver.php` implementing NotificationChannelDriverContract, constructor injecting InAppNotificationRepositoryContract, send() method creating InAppNotification record directly (no external API), validate() checking required fields (title, message, user_id), getChannelType() returning 'in_app', return notification ID as external_id | | |
| TASK-013 | Create WebhookChannelDriver at `packages/notifications/src/Services/Channels/WebhookChannelDriver.php` implementing NotificationChannelDriverContract, send() method using Http::post() to webhook URL with JSON payload, signature header using HMAC-SHA256, timeout 10 seconds, validate() checking URL format and payload structure, getChannelType() returning 'webhook', return webhook delivery log ID as external_id | | |
| TASK-014 | Create config file at `packages/notifications/config/notifications.php` with keys: channels.email.driver = 'sendgrid', channels.email.api_key = env('SENDGRID_API_KEY'), channels.email.from_address = env('MAIL_FROM_ADDRESS'), channels.sms.driver = 'twilio', channels.sms.account_sid = env('TWILIO_ACCOUNT_SID'), channels.sms.auth_token = env('TWILIO_AUTH_TOKEN'), channels.sms.from_number = env('TWILIO_FROM_NUMBER'), channels.push.driver = 'fcm', channels.push.server_key = env('FCM_SERVER_KEY'), retry.max_attempts = 5, retry.backoff_multiplier = 2, retry.initial_delay = 30 | | |
| TASK-015 | Register all channel drivers in NotificationsServiceProvider boot() method: app()->bind(EmailChannelDriver::class), app()->bind(SmsChannelDriver::class), app()->bind(PushChannelDriver::class), app()->bind(InAppChannelDriver::class), app()->bind(WebhookChannelDriver::class), inject into NotificationService constructor as array | | |
| TASK-016 | Add external package dependencies to composer.json: "sendgrid/sendgrid": "^8.0" for email, "twilio/sdk": "^7.0" for SMS, "kreait/firebase-php": "^7.0" for push notifications, "guzzlehttp/guzzle": "^7.0" for webhook HTTP requests | | |

### GOAL-003: Implement Delivery Retry Service with Exponential Backoff

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-NE-003 | Retry failed notifications with exponential backoff up to 5 attempts | | |
| PERF-003 | Exponential backoff delays (30s, 120s, 300s, 600s, 1200s) | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create DeliveryRetryServiceContract interface at `packages/notifications/src/Contracts/DeliveryRetryServiceContract.php` with methods: shouldRetry(NotificationLog $log): bool, calculateBackoff(int $attemptNumber): int (returns seconds), scheduleRetry(NotificationLog $log): void, markAsPermFailed(NotificationLog $log, string $reason): void | | |
| TASK-018 | Create DeliveryRetryService at `packages/notifications/src/Services/DeliveryRetryService.php` implementing DeliveryRetryServiceContract, shouldRetry() returning true if delivery_attempts < 5 and status is FAILED, calculateBackoff() using formula: initial_delay * (backoff_multiplier ^ (attemptNumber - 1)), e.g., 30 * (2^0) = 30s, 30 * (2^1) = 60s, 30 * (2^2) = 120s, 30 * (2^3) = 240s, 30 * (2^4) = 480s | | |
| TASK-019 | Implement scheduleRetry() method: increment delivery_attempts counter, calculate backoff delay, update scheduled_at to now() + backoff seconds, reset status to PENDING, dispatch SendNotificationJob with delay(backoff), log retry attempt with activitylog | | |
| TASK-020 | Implement markAsPermFailed() method: update status to FAILED, set error_message with failure reason, fire NotificationFailedEvent, do not schedule further retries, log permanent failure | | |
| TASK-021 | Create SendNotificationJob at `packages/notifications/src/Jobs/SendNotificationJob.php` implementing ShouldQueue, constructor accepting NotificationLog $log, handle() method injecting NotificationService, retrieving appropriate channel driver, calling driver->send() with log data, updating log with delivery status (SENT or FAILED), handling exceptions by calling DeliveryRetryService->scheduleRetry() or markAsPermFailed() if max attempts reached | | |
| TASK-022 | Configure SendNotificationJob: use tries = 1 (retries handled by DeliveryRetryService), timeout = 30 seconds, queue name based on NotificationPriority using $log->priority, middleware [WithoutOverlapping::class] to prevent duplicate sends, failed() method calling DeliveryRetryService->scheduleRetry() | | |
| TASK-023 | Create RetryFailedNotificationsCommand at `packages/notifications/src/Console/RetryFailedNotificationsCommand.php`, signature 'notifications:retry', description 'Retry failed notifications', handle() method querying NotificationLog where status = FAILED and delivery_attempts < 5, calling DeliveryRetryService->scheduleRetry() for each, display count of retried notifications | | |
| TASK-024 | Register RetryFailedNotificationsCommand in NotificationsServiceProvider boot() method using $this->commands([RetryFailedNotificationsCommand::class]) | | |
| TASK-025 | Create CircuitBreakerService at `packages/notifications/src/Services/CircuitBreakerService.php` for external service failure handling, track failure rate per channel using Redis, open circuit if failure rate > 50% in last 5 minutes, half-open circuit after 2 minutes to test recovery, close circuit if success rate improves, integrate with SendNotificationJob to check circuit state before sending | | |

### GOAL-004: Integrate Laravel Queue and Horizon for Async Processing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-NE-001 | Use Redis with Laravel Queue/Horizon | | |
| PR-NE-002 | Process 10,000+ notifications per minute | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Configure Laravel Queue in .env: QUEUE_CONNECTION=redis, REDIS_HOST=127.0.0.1, REDIS_PORT=6379, ensure redis driver installed and configured | | |
| TASK-027 | Install Laravel Horizon: composer require laravel/horizon, publish config with php artisan horizon:install, configure horizon.php with queues: notifications-critical, notifications-high, notifications, notifications-low, set balance strategy to 'auto', configure supervisor processes = 10, max processes = 20 | | |
| TASK-028 | Configure Horizon queue workers: notifications-critical queue with 3 workers, notifications-high with 3 workers, notifications with 2 workers, notifications-low with 2 workers, total 10 workers for parallel processing, set timeout = 60 seconds, memory limit = 128MB | | |
| TASK-029 | Create Horizon dashboard authentication in AuthServiceProvider: Gate::define('viewHorizon', function ($user) { return $user->hasRole('admin'); }), configure Horizon middleware in config/horizon.php | | |
| TASK-030 | Configure queue monitoring: set up Horizon metrics to track throughput (jobs per minute), wait time (time in queue), runtime (processing time), failed jobs, configure alerts for failed job rate > 5%, wait time > 10 seconds | | |
| TASK-031 | Create NotificationQueueHealthCheckCommand at `packages/notifications/src/Console/NotificationQueueHealthCheckCommand.php`, signature 'notifications:queue-health', checks queue length for each priority, warns if critical queue > 100 items, high queue > 500 items, normal queue > 1000 items, logs metrics to Laravel Log | | |
| TASK-032 | Add queue tags to SendNotificationJob for better monitoring: use $this->tags() returning ['notification', $log->channel_type, $log->priority, "tenant:{$log->tenant_id}"] | | |

### GOAL-005: Implement Delivery Status Tracking and Performance Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-005 | Track notification delivery status | | |
| PR-NE-001, PR-NE-002 | Performance benchmarks (< 3s delivery, 10k+/min) | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-033 | Create NotificationLogRepositoryContract interface at `packages/notifications/src/Contracts/NotificationLogRepositoryContract.php` with methods: create(array $data): NotificationLog, update(NotificationLog $log, array $data): NotificationLog, findById(int $id): ?NotificationLog, findByExternalId(string $externalId): ?NotificationLog, getByStatus(string $status, string $tenantId, int $limit = 100): Collection, getDeliveryStats(string $tenantId, Carbon $startDate, Carbon $endDate): array | | |
| TASK-034 | Create NotificationLogRepository at `packages/notifications/src/Repositories/NotificationLogRepository.php` implementing NotificationLogRepositoryContract, getDeliveryStats() method returning: total_sent, total_delivered, total_failed, average_delivery_time (in seconds), success_rate (percentage), failure_rate_by_channel (array), group results by channel_type and status | | |
| TASK-035 | Add updateDeliveryStatus() helper method to NotificationLogRepository accepting NotificationLog, status, externalId, errorMessage parameters, updating status, sent_at (if SENT), delivered_at (if DELIVERED), error_message, external_id, firing NotificationSentEvent or NotificationFailedEvent based on status | | |
| TASK-036 | Create NotificationSentEvent at `packages/notifications/src/Events/NotificationSentEvent.php` with constructor accepting NotificationLog $log, string $channel, string $recipient, implement ShouldBroadcast interface, broadcastOn() returning PrivateChannel for user, broadcastAs() returning 'notification.sent' | | |
| TASK-037 | Create NotificationFailedEvent at `packages/notifications/src/Events/NotificationFailedEvent.php` with constructor accepting NotificationLog $log, string $errorMessage, int $attemptNumber, fire when delivery fails and retry scheduled or max attempts reached | | |
| TASK-038 | Create NotificationDeliveredEvent at `packages/notifications/src/Events/NotificationDeliveredEvent.php` fired when external service confirms delivery (e.g., SendGrid webhook), update delivered_at timestamp | | |
| TASK-039 | Create webhook endpoint for external service callbacks at `packages/notifications/src/Http/Controllers/NotificationWebhookController.php`, POST /api/v1/notifications/webhooks/sendgrid, POST /api/v1/notifications/webhooks/twilio, validate webhook signature, update NotificationLog delivery status based on callback data | | |
| TASK-040 | Create Feature test at `packages/notifications/tests/Feature/MultiChannelDeliveryTest.php` testing: can send email notification, can send SMS notification, can send push notification, can send in-app notification, can send webhook notification, all channels update delivery log correctly, failed deliveries trigger retry | | |
| TASK-041 | Create Feature test at `packages/notifications/tests/Feature/DeliveryRetryTest.php` testing: failed notification schedules retry with correct backoff delay, max 5 retry attempts enforced, permanent failure after max attempts, exponential backoff formula correct (30s, 60s, 120s, 240s, 480s) | | |
| TASK-042 | Create Performance test at `packages/notifications/tests/Performance/NotificationThroughputTest.php` testing: can process 10,000 notifications in < 60 seconds (166/sec minimum), average delivery time < 3 seconds, queue workers scale to handle load, no memory leaks during sustained load | | |
| TASK-043 | Create Integration test testing SendGrid, Twilio, Firebase FCM drivers with real API calls (use test mode/sandbox), verify external_id returned, verify delivery status updated, verify webhook callbacks processed correctly | | |
| TASK-044 | Run `./vendor/bin/pest` to execute all tests, verify 80% coverage, run `./vendor/bin/pint` to format code, benchmark delivery throughput with `php artisan notifications:benchmark` (create command for load testing) | | |

## 3. Alternatives

- **ALT-001**: Use Laravel native notification system instead of custom service - Rejected because requires additional abstraction for retry logic and multi-channel strategy pattern
- **ALT-002**: Use Amazon SQS instead of Redis for queue - Rejected due to higher latency and cost
- **ALT-003**: Retry immediately without backoff - Rejected because can overwhelm external services and violate rate limits
- **ALT-004**: Store failed notifications in separate table - Rejected due to complexity; status field in NotificationLog sufficient

## 4. Dependencies

- **DEP-001**: `laravel/horizon` ^5.0 for queue management and monitoring
- **DEP-002**: `sendgrid/sendgrid` ^8.0 for email delivery
- **DEP-003**: `twilio/sdk` ^7.0 for SMS delivery
- **DEP-004**: `kreait/firebase-php` ^7.0 for push notifications
- **DEP-005**: `guzzlehttp/guzzle` ^7.0 for webhook HTTP requests
- **DEP-006**: Redis 6+ configured and running for queue and circuit breaker
- **DEP-007**: External service accounts configured (SendGrid, Twilio, Firebase FCM)
- **DEP-008**: Supervisor or systemd configured to run Horizon workers

## 5. Files

**Services:**
- `packages/notifications/src/Services/NotificationService.php` - Multi-channel notification service
- `packages/notifications/src/Services/DeliveryRetryService.php` - Retry logic with exponential backoff
- `packages/notifications/src/Services/CircuitBreakerService.php` - External service failure handling
- `packages/notifications/src/Services/Channels/EmailChannelDriver.php` - SendGrid integration
- `packages/notifications/src/Services/Channels/SmsChannelDriver.php` - Twilio integration
- `packages/notifications/src/Services/Channels/PushChannelDriver.php` - Firebase FCM integration
- `packages/notifications/src/Services/Channels/InAppChannelDriver.php` - In-app notification driver
- `packages/notifications/src/Services/Channels/WebhookChannelDriver.php` - Webhook delivery driver

**Contracts:**
- `packages/notifications/src/Contracts/NotificationServiceContract.php` - Notification service interface
- `packages/notifications/src/Contracts/NotificationChannelDriverContract.php` - Channel driver interface
- `packages/notifications/src/Contracts/DeliveryRetryServiceContract.php` - Retry service interface
- `packages/notifications/src/Contracts/NotificationLogRepositoryContract.php` - Delivery log repository interface

**Jobs:**
- `packages/notifications/src/Jobs/SendNotificationJob.php` - Async notification delivery job

**Events:**
- `packages/notifications/src/Events/NotificationSentEvent.php` - Notification sent successfully
- `packages/notifications/src/Events/NotificationFailedEvent.php` - Notification delivery failed
- `packages/notifications/src/Events/NotificationDeliveredEvent.php` - External confirmation received

**Commands:**
- `packages/notifications/src/Console/RetryFailedNotificationsCommand.php` - Manual retry command
- `packages/notifications/src/Console/NotificationQueueHealthCheckCommand.php` - Queue monitoring command

**Enums:**
- `packages/notifications/src/Enums/NotificationChannel.php` - Channel types enum
- `packages/notifications/src/Enums/NotificationStatus.php` - Delivery status enum
- `packages/notifications/src/Enums/NotificationPriority.php` - Priority levels enum

**Tests:**
- `packages/notifications/tests/Feature/MultiChannelDeliveryTest.php` - Multi-channel delivery tests
- `packages/notifications/tests/Feature/DeliveryRetryTest.php` - Retry logic tests
- `packages/notifications/tests/Performance/NotificationThroughputTest.php` - Performance benchmarks
- `packages/notifications/tests/Integration/ExternalServiceTest.php` - External API integration tests

## 6. Testing

- **TEST-001**: Verify NotificationService sends notifications to all 5 channels (email, SMS, push, in-app, webhook)
- **TEST-002**: Verify EmailChannelDriver integrates with SendGrid API and returns external message ID
- **TEST-003**: Verify SmsChannelDriver integrates with Twilio API and returns SID
- **TEST-004**: Verify PushChannelDriver integrates with Firebase FCM and returns message ID
- **TEST-005**: Verify WebhookChannelDriver posts to URL with HMAC signature
- **TEST-006**: Verify failed delivery triggers retry with correct exponential backoff delays (30s, 60s, 120s, 240s, 480s)
- **TEST-007**: Verify max 5 retry attempts enforced, permanent failure after 5th attempt
- **TEST-008**: Verify SendNotificationJob updates NotificationLog status correctly (SENT, FAILED, DELIVERED)
- **TEST-009**: Verify DeliveryRetryService calculates backoff correctly: 30 * (2 ^ (attempt - 1))
- **TEST-010**: Verify CircuitBreakerService opens circuit after 50% failure rate in 5 minutes
- **TEST-011**: Verify Laravel Horizon processes jobs from 4 priority queues (critical, high, normal, low)
- **TEST-012**: Verify throughput benchmark: 10,000 notifications processed in < 60 seconds (PR-NE-002)
- **TEST-013**: Verify average delivery time < 3 seconds from queue to sent status (PR-NE-001)
- **TEST-014**: Verify webhook callbacks from SendGrid/Twilio update delivery status correctly
- **TEST-015**: Verify NotificationSentEvent and NotificationFailedEvent broadcast correctly
- **TEST-016**: Verify all tests pass with `./vendor/bin/pest` and achieve 80% coverage

## 7. Risks & Assumptions

- **RISK-001**: External service downtime (SendGrid, Twilio, Firebase) causes notification failures - Mitigation: Circuit breaker pattern and retry logic
- **RISK-002**: Queue congestion during peak load - Mitigation: Horizon auto-scaling and priority queues
- **RISK-003**: Webhook signature validation bypass - Mitigation: HMAC-SHA256 validation on all callbacks
- **RISK-004**: Memory leaks in long-running queue workers - Mitigation: Horizon memory limit and supervisor restart
- **ASSUMPTION-001**: External service APIs remain stable and backward compatible
- **ASSUMPTION-002**: Redis has sufficient memory for queue storage during peak load
- **ASSUMPTION-003**: Supervisor or systemd configured to restart Horizon workers on failure
- **ASSUMPTION-004**: SendGrid, Twilio, Firebase accounts have sufficient quota for daily volume

## 8. KIV for Future Implementations

- **KIV-001**: Support additional channels (Slack, Microsoft Teams, Discord)
- **KIV-002**: Implement adaptive retry strategies based on error types (e.g., rate limit vs network error)
- **KIV-003**: Add notification priority routing to different queue workers for SLA guarantees
- **KIV-004**: Implement notification batching for digest mode (combine multiple notifications into one email)
- **KIV-005**: Add delivery time optimization based on user timezone and engagement patterns
- **KIV-006**: Support fallback channels (e.g., SMS if email fails)

## 9. Related PRD / Further Reading

- [PRD01-SUB22: Notifications & Events](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md) - Complete Sub-PRD requirements
- [Master PRD](../prd/PRD01-MVP.md) - Master PRD Section F.2.3 (Notifications & Events module)
- [Laravel Queue Documentation](https://laravel.com/docs/queues) - Queue system and job processing
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon) - Queue monitoring and management
- [SendGrid API Documentation](https://docs.sendgrid.com/api-reference) - Email delivery API
- [Twilio API Documentation](https://www.twilio.com/docs/sms) - SMS delivery API
- [Firebase Cloud Messaging Documentation](https://firebase.google.com/docs/cloud-messaging) - Push notification API
