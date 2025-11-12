---
plan: Implement Event Subscriptions and User Notification Preferences
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, notifications, events, subscriptions, preferences, user-settings, messaging]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the event subscription system with user-configurable notification preferences, allowing users to subscribe to business events, select delivery channels, configure digest mode, set quiet hours, and manage notification frequency. It includes the event listener that broadcasts notifications to subscribers based on their preferences and comprehensive testing across modules.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-NE-003**: Support event subscriptions with user-configurable preferences
- **FR-NE-006**: Support notification grouping and digest mode to reduce noise
- **BR-NE-001**: Users must opt-in for non-critical notifications
- **BR-NE-002**: Critical alerts (security, compliance) cannot be disabled by users
- **DR-NE-002**: Maintain user preferences for notification channels and frequency
- **IR-NE-001**: Integrate with all modules via event-driven architecture

**Security Requirements:**
- **SEC-001**: Users can only manage their own notification preferences
- **SEC-002**: Critical alert subscriptions cannot be disabled (enforced at policy level)
- **SEC-003**: Tenant isolation on all subscription and preference queries

**Architectural Constraints:**
- **ARCH-001**: Event-driven architecture for cross-module integration
- **ARCH-002**: Contract-driven service design for preference matching
- **ARCH-003**: Repository pattern for subscription data access
- **ARCH-004**: Policy-based authorization for preference management

**Performance Constraints:**
- **PERF-001**: Preference matching must complete in < 50ms per event
- **PERF-002**: Event subscription queries must return in < 100ms
- **PERF-003**: Preference caching to reduce database queries

**Guidelines:**
- **GUD-001**: Follow Laravel 12 event/listener conventions
- **GUD-002**: Use PHP 8.2+ features
- **GUD-003**: All code must pass Pint formatting
- **GUD-004**: Minimum 80% test coverage

**Patterns:**
- **PAT-001**: Observer pattern for event listening
- **PAT-002**: Strategy pattern for preference matching logic
- **PAT-003**: Repository pattern for data access
- **PAT-004**: Policy pattern for authorization

## 2. Implementation Steps

### GOAL-001: Create Event Subscription Service with Preference Matching

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-003 | Event subscriptions with user preferences | | |
| PERF-001 | Preference matching < 50ms per event | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create EventSubscriptionServiceContract interface at `packages/notifications/src/Contracts/EventSubscriptionServiceContract.php` with methods: subscribe(int $userId, string $eventType, array $channels): EventSubscription, unsubscribe(int $userId, string $eventType): bool, getUserSubscriptions(int $userId, string $tenantId): Collection, getSubscribersByEvent(string $eventType, string $tenantId): Collection, isSubscribed(int $userId, string $eventType): bool | | |
| TASK-002 | Create PreferenceMatchingServiceContract interface at `packages/notifications/src/Contracts/PreferenceMatchingServiceContract.php` with methods: shouldNotify(int $userId, string $eventType, string $channel, Carbon $timestamp): bool, getActiveChannels(int $userId, string $eventType): array, isInQuietHours(int $userId, Carbon $timestamp): bool, shouldDigest(int $userId, string $channel): bool | | |
| TASK-003 | Create EventSubscriptionService at `packages/notifications/src/Services/EventSubscriptionService.php` implementing EventSubscriptionServiceContract, constructor injecting EventSubscriptionRepositoryContract, subscribe() method creating or updating EventSubscription with notification_channels array, validating channel types using NotificationChannel enum, unsubscribe() soft-deleting or deactivating subscription | | |
| TASK-004 | Add getSubscribersByEvent() method using cached query: cache key format "subscriptions:event:{event_type}:{tenant_id}", cache TTL 30 minutes, returns Collection of users with their preferred channels, eager load user and preference relationships | | |
| TASK-005 | Create PreferenceMatchingService at `packages/notifications/src/Services/PreferenceMatchingService.php` implementing PreferenceMatchingServiceContract, constructor injecting UserNotificationPreferenceRepositoryContract, shouldNotify() method checking: 1) channel enabled for user, 2) not in quiet hours, 3) subscription active, 4) digest mode not delaying notification | | |
| TASK-006 | Implement isInQuietHours() method: get user's quiet_hours_start and quiet_hours_end from preferences, compare $timestamp time component with range, handle midnight crossover (e.g., 22:00 to 06:00), return true if timestamp falls in quiet hours | | |
| TASK-007 | Implement shouldDigest() method: check if digest_mode enabled for channel, check digest_frequency (hourly, daily, weekly), return true if notification should be batched for later delivery | | |
| TASK-008 | Implement getActiveChannels() method: query user preferences where is_enabled = true and channel in event subscription channels, filter out channels in quiet hours, cache result for 15 minutes with key "preferences:channels:{user_id}:{event_type}" | | |
| TASK-009 | Create SubscribeToEventAction at `packages/notifications/src/Actions/SubscribeToEventAction.php` using AsAction trait, handle(int $userId, string $eventType, array $channels): EventSubscription method injecting EventSubscriptionServiceContract, validating channels, creating subscription, invalidating subscriber cache | | |

### GOAL-002: Implement User Notification Preference Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-006, DR-NE-002 | User preferences for channels and frequency | | |
| BR-NE-001 | Opt-in requirement for non-critical notifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create UserNotificationPreferenceRepositoryContract interface at `packages/notifications/src/Contracts/UserNotificationPreferenceRepositoryContract.php` with methods: getUserPreferences(int $userId, string $tenantId): Collection, updatePreference(int $userId, string $channel, array $data): UserNotificationPreference, getPreferenceByChannel(int $userId, string $channel): ?UserNotificationPreference, createDefaultPreferences(int $userId, string $tenantId): Collection | | |
| TASK-011 | Create UserNotificationPreferenceRepository at `packages/notifications/src/Repositories/UserNotificationPreferenceRepository.php` implementing interface, createDefaultPreferences() method creating preferences for all channels (email, sms, push, in_app) with is_enabled = true (opt-in by default for all channels), digest_mode = false, quiet_hours null | | |
| TASK-012 | Add getUserPreferences() method with caching: cache key "preferences:user:{user_id}:{tenant_id}", cache TTL 1 hour, invalidate cache when preferences updated, eager load relationships | | |
| TASK-013 | Create UpdateUserPreferencesAction at `packages/notifications/src/Actions/UpdateUserPreferencesAction.php` using AsAction trait, handle(int $userId, array $preferences): Collection method validating preference data (channel_type, is_enabled, digest_mode, digest_frequency, quiet_hours_start, quiet_hours_end), updating or creating preferences, invalidating preference cache | | |
| TASK-014 | Create GetUserPreferencesAction at `packages/notifications/src/Actions/GetUserPreferencesAction.php` using AsAction trait, handle(int $userId): Collection method returning user's notification preferences for all channels, if no preferences exist, create defaults using createDefaultPreferences() | | |
| TASK-015 | Create SendTestNotificationAction at `packages/notifications/src/Actions/SendTestNotificationAction.php` using AsAction trait, handle(int $userId, string $channel): NotificationLog method sending test notification to verify channel configuration, using NotificationService->send(), display success or error message | | |
| TASK-016 | Create UserNotificationPreferencePolicy at `packages/notifications/src/Policies/UserNotificationPreferencePolicy.php` with methods: view(User $user, UserNotificationPreference $preference): bool checking $user->id === $preference->user_id, update(User $user, UserNotificationPreference $preference): bool with same check, ensuring users can only manage their own preferences | | |
| TASK-017 | Register UserNotificationPreferencePolicy in AuthServiceProvider using Gate::policy(UserNotificationPreference::class, UserNotificationPreferencePolicy::class) | | |

### GOAL-003: Create Event Listener for Broadcasting to Subscribers

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-NE-001 | Integration with all modules via events | | |
| BR-NE-002 | Critical alerts cannot be disabled | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-018 | Create BroadcastEventToSubscribersListener at `packages/notifications/src/Listeners/BroadcastEventToSubscribersListener.php` implementing ShouldQueue, handle($event) method: 1) Get event class name, 2) Query subscribers for this event type, 3) For each subscriber, check preferences using PreferenceMatchingService, 4) If shouldNotify returns true, dispatch SendNotificationJob with user's preferred channels | | |
| TASK-019 | Add logic to identify critical events: create CriticalEventMarker interface with isCritical(): bool method, check if $event implements CriticalEventMarker, if critical, bypass user preferences and send to all channels (email + in-app minimum), log critical event broadcast | | |
| TASK-020 | Add event data extraction: create NotifiableEvent interface with methods: getNotificationData(): array, getNotificationTemplate(): string, implement in all business events, extract data for template rendering | | |
| TASK-021 | Register BroadcastEventToSubscribersListener as wildcard listener in EventServiceProvider: Event::listen('*', BroadcastEventToSubscribersListener::class), this captures ALL events fired in system, filter for NotifiableEvent instances in handle() method | | |
| TASK-022 | Add batching logic: if user has digest_mode enabled, queue notification for later batching using DigestNotificationJob scheduled based on digest_frequency (hourly, daily, weekly), store in pending_digest_notifications table temporarily | | |
| TASK-023 | Create DigestNotificationJob at `packages/notifications/src/Jobs/DigestNotificationJob.php` implementing ShouldQueue, scheduled to run: hourly (every hour at :00), daily (every day at 9:00 AM), weekly (every Monday at 9:00 AM), consolidate pending notifications into single digest email/message, send using NotificationService, clear pending digest queue | | |
| TASK-024 | Create migration `2025_01_01_000006_create_pending_digest_notifications_table.php` with columns: id, tenant_id, user_id, notification_type, event_type, event_data (jsonb), scheduled_for (timestamp), created_at, indexes on tenant_id, user_id, scheduled_for | | |
| TASK-025 | Add performance optimization: use Laravel's queue batch feature to process multiple subscribers in parallel, batch size 100 users per job, process batches concurrently to achieve < 50ms per event target (PERF-001) | | |

### GOAL-004: Create API Endpoints for Subscription and Preference Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-003 | API for managing subscriptions and preferences | | |
| SEC-001, SEC-002 | Authorization and security checks | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create EventSubscriptionController at `packages/notifications/src/Http/Controllers/EventSubscriptionController.php` with methods: index() listing user subscriptions using GetUserSubscriptionsAction, store() subscribing to event using SubscribeToEventAction with validation (event_type required, channels array required), destroy() unsubscribing using UnsubscribeFromEventAction, authorize using $this->authorize('manageSubscriptions', EventSubscription::class) | | |
| TASK-027 | Create StoreEventSubscriptionRequest at `packages/notifications/src/Http/Requests/StoreEventSubscriptionRequest.php` with rules: event_type => ['required', 'string', 'max:255'], channels => ['required', 'array', 'min:1'], channels.* => ['required', 'string', Rule::in(NotificationChannel::values())], authorize() returning true (user can subscribe to any event) | | |
| TASK-028 | Create EventSubscriptionResource at `packages/notifications/src/Http/Resources/EventSubscriptionResource.php` returning: id, event_type, event_name (human-readable), channels (array), is_active, created_at (ISO 8601), links (self, unsubscribe) | | |
| TASK-029 | Create UserNotificationPreferenceController at `packages/notifications/src/Http/Controllers/UserNotificationPreferenceController.php` with methods: index() listing user preferences using GetUserPreferencesAction, update() updating preferences using UpdateUserPreferencesAction, testNotification() sending test using SendTestNotificationAction, authorize using policy | | |
| TASK-030 | Create UpdateUserPreferencesRequest at `packages/notifications/src/Http/Requests/UpdateUserPreferencesRequest.php` with rules: preferences => ['required', 'array'], preferences.*.channel_type => ['required', 'string', Rule::in(['email', 'sms', 'push', 'in_app'])], preferences.*.is_enabled => ['boolean'], preferences.*.digest_mode => ['boolean'], preferences.*.digest_frequency => ['nullable', 'string', Rule::in(['hourly', 'daily', 'weekly'])], preferences.*.quiet_hours_start => ['nullable', 'date_format:H:i'], preferences.*.quiet_hours_end => ['nullable', 'date_format:H:i'] | | |
| TASK-031 | Create UserNotificationPreferenceResource at `packages/notifications/src/Http/Resources/UserNotificationPreferenceResource.php` returning: id, channel_type, channel_name (human-readable), is_enabled, digest_mode, digest_frequency, quiet_hours (start/end formatted), updated_at (ISO 8601), links (self, test) | | |
| TASK-032 | Register API routes in `packages/notifications/routes/api.php`: Route::prefix('notifications')->middleware(['auth:sanctum', 'tenant'])->group with routes: GET /subscriptions (index), POST /subscriptions (store), DELETE /subscriptions/{id} (destroy), GET /preferences (index), PATCH /preferences (update), POST /preferences/test (testNotification) | | |

### GOAL-005: Testing with Cross-Module Event Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-NE-001 | Event integration testing | | |
| GUD-004 | 80% test coverage | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-033 | Create Feature test at `packages/notifications/tests/Feature/EventSubscriptionTest.php` testing: can subscribe to event via API, can unsubscribe from event, can list user subscriptions, subscription creates entry in database, unsubscribe removes or deactivates subscription, tenant isolation working | | |
| TASK-034 | Create Feature test at `packages/notifications/tests/Feature/UserNotificationPreferenceTest.php` testing: can get user preferences via API, can update preferences, can enable/disable channels, can set digest mode, can configure quiet hours, can send test notification, preference updates invalidate cache | | |
| TASK-035 | Create Feature test at `packages/notifications/tests/Feature/EventBroadcastingTest.php` testing: BroadcastEventToSubscribersListener fires when event occurs, subscribers receive notifications on preferred channels, non-subscribers do not receive notifications, critical events bypass preferences, quiet hours respected, digest mode queues notifications | | |
| TASK-036 | Create Integration test at `packages/notifications/tests/Integration/CrossModuleEventTest.php` testing integration with: WorkflowApprovedEvent (SUB21), PurchaseOrderApprovedEvent (SUB16), InvoiceOverdueEvent (SUB12), PaymentReceivedEvent (SUB09), verify BroadcastEventToSubscribersListener captures these events, verify subscribers notified correctly | | |
| TASK-037 | Create Unit test at `packages/notifications/tests/Unit/PreferenceMatchingTest.php` testing: shouldNotify() logic correct, isInQuietHours() handles midnight crossover, shouldDigest() checks digest mode, getActiveChannels() filters correctly, preference caching working | | |
| TASK-038 | Create Unit test at `packages/notifications/tests/Unit/EventSubscriptionServiceTest.php` testing: subscribe() creates subscription, unsubscribe() removes subscription, getSubscribersByEvent() returns correct users, subscription cache invalidation working | | |
| TASK-039 | Create Performance test at `packages/notifications/tests/Performance/PreferenceMatchingPerformanceTest.php` testing: preference matching completes in < 50ms per event (PERF-001), event subscription query returns in < 100ms (PERF-002), cache reduces database queries by 90%+, batch processing improves throughput | | |
| TASK-040 | Mock external events from other modules in tests using Event::fake(), fire WorkflowApprovedEvent, PurchaseOrderApprovedEvent, verify BroadcastEventToSubscribersListener called, verify NotificationService->send() called for subscribers | | |
| TASK-041 | Run `./vendor/bin/pest` to execute all tests, verify 80% coverage, run `./vendor/bin/pint` to format code, verify all tests pass | | |

## 3. Alternatives

- **ALT-001**: Use database polling instead of event listeners for notification triggering - Rejected due to higher latency and database load
- **ALT-002**: Send all notifications immediately without preference checking - Rejected because violates BR-NE-001 (opt-in requirement)
- **ALT-003**: Store preferences as JSON in users table instead of separate table - Rejected due to lack of flexibility and query performance
- **ALT-004**: Use third-party subscription management service - Rejected to maintain data ownership and reduce external dependencies

## 4. Dependencies

- **DEP-001**: `packages/notifications/src/Models/EventSubscription.php` from PLAN01
- **DEP-002**: `packages/notifications/src/Models/UserNotificationPreference.php` from PLAN01
- **DEP-003**: `packages/notifications/src/Services/NotificationService.php` from PLAN02
- **DEP-004**: `packages/notifications/src/Jobs/SendNotificationJob.php` from PLAN02
- **DEP-005**: Laravel Event system configured in EventServiceProvider
- **DEP-006**: Redis configured for preference caching
- **DEP-007**: Business events from other modules implementing NotifiableEvent interface

## 5. Files

**Services:**
- `packages/notifications/src/Services/EventSubscriptionService.php` - Subscription management service
- `packages/notifications/src/Services/PreferenceMatchingService.php` - Preference matching logic

**Contracts:**
- `packages/notifications/src/Contracts/EventSubscriptionServiceContract.php` - Subscription service interface
- `packages/notifications/src/Contracts/PreferenceMatchingServiceContract.php` - Preference matching interface
- `packages/notifications/src/Contracts/UserNotificationPreferenceRepositoryContract.php` - Preference repository interface

**Repositories:**
- `packages/notifications/src/Repositories/UserNotificationPreferenceRepository.php` - Preference data access

**Actions:**
- `packages/notifications/src/Actions/SubscribeToEventAction.php` - Subscribe to event
- `packages/notifications/src/Actions/UpdateUserPreferencesAction.php` - Update preferences
- `packages/notifications/src/Actions/GetUserPreferencesAction.php` - Get user preferences
- `packages/notifications/src/Actions/SendTestNotificationAction.php` - Send test notification

**Listeners:**
- `packages/notifications/src/Listeners/BroadcastEventToSubscribersListener.php` - Event broadcaster

**Jobs:**
- `packages/notifications/src/Jobs/DigestNotificationJob.php` - Digest notification batch processing

**Controllers:**
- `packages/notifications/src/Http/Controllers/EventSubscriptionController.php` - Subscription API
- `packages/notifications/src/Http/Controllers/UserNotificationPreferenceController.php` - Preference API

**Requests:**
- `packages/notifications/src/Http/Requests/StoreEventSubscriptionRequest.php` - Subscription validation
- `packages/notifications/src/Http/Requests/UpdateUserPreferencesRequest.php` - Preference validation

**Resources:**
- `packages/notifications/src/Http/Resources/EventSubscriptionResource.php` - Subscription JSON response
- `packages/notifications/src/Http/Resources/UserNotificationPreferenceResource.php` - Preference JSON response

**Policies:**
- `packages/notifications/src/Policies/UserNotificationPreferencePolicy.php` - Preference authorization

**Interfaces:**
- `packages/notifications/src/Contracts/CriticalEventMarker.php` - Critical event interface
- `packages/notifications/src/Contracts/NotifiableEvent.php` - Notifiable event interface

**Migrations:**
- `packages/notifications/database/migrations/2025_01_01_000006_create_pending_digest_notifications_table.php`

**Tests:**
- `packages/notifications/tests/Feature/EventSubscriptionTest.php` - Subscription API tests
- `packages/notifications/tests/Feature/UserNotificationPreferenceTest.php` - Preference API tests
- `packages/notifications/tests/Feature/EventBroadcastingTest.php` - Event broadcasting tests
- `packages/notifications/tests/Integration/CrossModuleEventTest.php` - Cross-module integration tests
- `packages/notifications/tests/Unit/PreferenceMatchingTest.php` - Preference matching unit tests
- `packages/notifications/tests/Unit/EventSubscriptionServiceTest.php` - Subscription service unit tests
- `packages/notifications/tests/Performance/PreferenceMatchingPerformanceTest.php` - Performance benchmarks

## 6. Testing

- **TEST-001**: Verify EventSubscriptionService creates subscriptions with correct channels
- **TEST-002**: Verify unsubscribe removes or deactivates subscription
- **TEST-003**: Verify getSubscribersByEvent returns correct subscribers with cache
- **TEST-004**: Verify PreferenceMatchingService shouldNotify() logic works correctly
- **TEST-005**: Verify isInQuietHours() handles midnight crossover (e.g., 22:00-06:00)
- **TEST-006**: Verify shouldDigest() checks digest_mode and digest_frequency correctly
- **TEST-007**: Verify getActiveChannels() filters channels based on preferences
- **TEST-008**: Verify BroadcastEventToSubscribersListener captures events and notifies subscribers
- **TEST-009**: Verify critical events bypass user preferences (BR-NE-002)
- **TEST-010**: Verify digest mode queues notifications in pending_digest_notifications table
- **TEST-011**: Verify DigestNotificationJob consolidates and sends digest notifications
- **TEST-012**: Verify API endpoints work: subscribe, unsubscribe, get/update preferences, test notification
- **TEST-013**: Verify preference caching reduces database queries
- **TEST-014**: Verify preference matching completes in < 50ms per event (PERF-001)
- **TEST-015**: Verify subscription queries return in < 100ms (PERF-002)
- **TEST-016**: Verify cross-module event integration with Workflow, Purchasing, Accounting modules
- **TEST-017**: Verify tenant isolation on subscriptions and preferences
- **TEST-018**: Verify UserNotificationPreferencePolicy enforces user-only access
- **TEST-019**: Verify all tests pass with `./vendor/bin/pest` and achieve 80% coverage

## 7. Risks & Assumptions

- **RISK-001**: Event listener performance degrades with large number of subscribers - Mitigation: Batch processing and caching
- **RISK-002**: Preference cache invalidation failures cause stale data - Mitigation: Short TTL (15-60 minutes) and cache tagging
- **RISK-003**: Digest notification delays cause user confusion - Mitigation: Clear UI indication of digest mode and timing
- **RISK-004**: Wildcard event listener captures too many events - Mitigation: Filter for NotifiableEvent interface
- **ASSUMPTION-001**: All business events implement NotifiableEvent interface for notification data
- **ASSUMPTION-002**: Redis available and configured for preference caching
- **ASSUMPTION-003**: Users understand quiet hours and digest mode concepts
- **ASSUMPTION-004**: Critical events are clearly marked with CriticalEventMarker interface

## 8. KIV for Future Implementations

- **KIV-001**: Machine learning-based notification frequency optimization
- **KIV-002**: Smart digest grouping by topic or priority
- **KIV-003**: Notification preview before subscribing to events
- **KIV-004**: Bulk subscription management (subscribe to all workflow events at once)
- **KIV-005**: Notification analytics (open rates, engagement by event type)
- **KIV-006**: Recommendation engine for suggested subscriptions based on user role

## 9. Related PRD / Further Reading

- [PRD01-SUB22: Notifications & Events](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md) - Complete Sub-PRD requirements
- [Master PRD](../prd/PRD01-MVP.md) - Master PRD Section F.2.3 (Notifications & Events module)
- [PRD01-SUB21: Workflow Engine](../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md) - Workflow event integration
- [Laravel Events Documentation](https://laravel.com/docs/events) - Event system and listeners
- [Laravel Authorization Documentation](https://laravel.com/docs/authorization) - Policy implementation
