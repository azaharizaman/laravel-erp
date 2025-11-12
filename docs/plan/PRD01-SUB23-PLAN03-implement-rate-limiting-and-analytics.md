---
plan: PRD01-SUB23-PLAN03 - Rate Limiting & Analytics
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, api-gateway, rate-limiting, analytics, monitoring, performance]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers **Rate Limiting & Analytics** for the API Gateway & Documentation module (PRD01-SUB23). It implements tiered rate limiting per API key, API usage metrics collection, request logging with performance analysis, and real-time monitoring dashboards for API consumption patterns.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **SR-API-001**: Implement rate limiting per API key with tiered plans (basic, standard, premium, unlimited)
- **DR-API-001**: Log all API requests with response times and status codes
- **DR-API-002**: Store API usage metrics for analytics and billing
- **PR-API-002**: Support 10,000+ API requests per second with horizontal scaling
- **EV-API-002**: RateLimitExceededEvent when API quota is reached

**Security Constraints:**
- **SEC-007**: Rate limiting cannot be bypassed by multiple API keys from same tenant
- **SEC-008**: Rate limit enforcement must use atomic Redis operations to prevent race conditions
- **SEC-009**: Sensitive data in request/response logs must be redacted (passwords, tokens, keys)

**Performance Constraints:**
- **CON-007**: Rate limit check must complete in < 1ms via Redis lookup
- **CON-008**: API request logging must not exceed 5ms latency per request
- **CON-009**: Metrics aggregation must process 10,000+ requests/second without blocking

**Guidelines:**
- **GUD-009**: Use sliding window rate limiting algorithm
- **GUD-010**: Cache rate limit status in Redis with TTL matching time window
- **GUD-011**: Log all requests asynchronously via queue to prevent latency impact
- **GUD-012**: Aggregate metrics hourly and daily for analytics

**Patterns:**
- **PAT-008**: Use Token Bucket pattern for rate limiting algorithm
- **PAT-009**: Apply Decorator pattern for logging middleware
- **PAT-010**: Use Observer pattern for metrics aggregation events

## 2. Implementation Steps

### GOAL-001: Implement Tiered Rate Limiting

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| SR-API-001 | Tiered rate limiting per API key | | |
| EV-API-002 | RateLimitExceededEvent emission | | |
| CON-007 | Rate limit check < 1ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create rate limit tier enum in `packages/api-gateway/src/Enums/RateLimitTier.php` with values: BASIC (10/min), STANDARD (60/min), PREMIUM (600/min), UNLIMITED | | |
| TASK-002 | Create rate limit service in `packages/api-gateway/src/Services/RateLimiterService.php` with methods: checkRateLimit, incrementCounter, getRemainingRequests, getResetTime | | |
| TASK-003 | Implement sliding window algorithm in `checkRateLimit(ApiKey $key, string $endpoint): bool` using Redis sorted sets | | |
| TASK-004 | Use Redis key format: `rate_limit:{api_key_id}:{endpoint}:{timestamp_window}` for efficient window-based tracking | | |
| TASK-005 | Implement token bucket variant: decrement counter on each request, reset daily at tenant's configured reset time (default 00:00 UTC) | | |
| TASK-006 | Create rate limit middleware in `app/Http/Middleware/EnforceRateLimit.php` that calls RateLimiterService->checkRateLimit before processing request | | |
| TASK-007 | Add response headers for rate limit status: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset | | |
| TASK-008 | Return HTTP 429 (Too Many Requests) when rate limit exceeded with Retry-After header | | |
| TASK-009 | Create RateLimitExceededEvent in `packages/api-gateway/src/Events/RateLimitExceededEvent.php` with properties: apiKeyId, endpoint, requestCount, limit | | |
| TASK-010 | Create listener `packages/api-gateway/src/Listeners/LogRateLimitExceededListener.php` that logs event to monitoring system | | |
| TASK-011 | Create contract `packages/api-gateway/src/Contracts/RateLimiterServiceContract.php` with rate limiting interface | | |
| TASK-012 | Implement rate limit repository in `packages/api-gateway/src/Repositories/RateLimitRepository.php` for Redis operations | | |
| TASK-013 | Add cumulative rate limiting: check tenant-wide limit (sum of all API keys) to prevent circumvention via multiple keys | | |
| TASK-014 | Implement graceful degradation: if Redis unavailable, allow requests but log warning (don't fail) | | |
| TASK-015 | Write unit tests for rate limiting: window calculation, counter management, tier limits, reset timing (10 tests) | | |

### GOAL-002: Implement API Request Logging & Metrics

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-API-001 | Log all API requests with response times and status codes | | |
| DR-API-002 | Store API usage metrics for analytics and billing | | |
| CON-008 | Request logging < 5ms latency | | |
| SEC-009 | Redact sensitive data in logs | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create API request log model in `packages/api-gateway/src/Models/ApiRequestLog.php` with columns: tenant_id, api_key_id, user_id, request_method, request_path, response_status_code, response_time_ms, ip_address, user_agent, api_version, created_at | | |
| TASK-017 | Create API request logging service in `packages/api-gateway/src/Services/ApiRequestLoggingService.php` with method `logRequest(Request $request, Response $response, int $responseTimeMs)` | | |
| TASK-018 | Create request logging middleware in `app/Http/Middleware/LogApiRequest.php` that captures request/response and queues async logging | | |
| TASK-019 | Create queued job `app/Jobs/LogApiRequestJob.php` for async logging (queue connection: 'database' or 'redis') | | |
| TASK-020 | Implement request data sanitizer: redact Authorization headers, password fields, API keys, tokens from logs | | |
| TASK-021 | Store only request path and method, not full body (for privacy) - capture full body for debugging if needed | | |
| TASK-022 | Create API usage metrics model in `packages/api-gateway/src/Models/ApiUsageMetric.php` with columns: tenant_id, api_key_id, metric_date, total_requests, successful_requests, failed_requests, rate_limited_requests, avg_response_time_ms | | |
| TASK-023 | Create metrics aggregation service in `packages/api-gateway/src/Services/MetricsAggregationService.php` with methods: aggregateHourly, aggregateDaily, calculateStats | | |
| TASK-024 | Create queued job `app/Jobs/AggregateApiMetricsJob.php` that runs hourly via scheduler to aggregate metrics from request logs | | |
| TASK-025 | Add metrics tracking: count successful (2xx), error (4xx/5xx), rate limited (429), calculate average response time per endpoint | | |
| TASK-026 | Create metrics repository contract in `packages/api-gateway/src/Contracts/MetricsRepositoryContract.php` | | |
| TASK-027 | Implement metrics repository in `packages/api-gateway/src/Repositories/MetricsRepository.php` with query optimization for analytics | | |
| TASK-028 | Add database indexes on API request logs: tenant_id, api_key_id, created_at for efficient querying | | |
| TASK-029 | Implement log retention policy: delete logs older than 90 days via scheduled command | | |
| TASK-030 | Create Artisan command `php artisan api:cleanup-logs` to purge old logs and archive to cold storage (optional) | | |
| TASK-031 | Write unit tests for logging service: sanitization, metrics calculation, aggregation logic (8 tests) | | |

### GOAL-003: Implement Analytics & Monitoring APIs

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-API-001, DR-API-002 | API endpoints for usage analytics | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-032 | Create analytics controller in `packages/api-gateway/src/Http/Controllers/AnalyticsController.php` | | |
| TASK-033 | Create endpoint GET /api/v1/gateway/usage to return API usage metrics: total_requests, successful_requests, error_rate, avg_response_time | | |
| TASK-034 | Implement usage endpoint with filters: date range (from/to), api_key_id, endpoint_path, group_by (day/hour) | | |
| TASK-035 | Create endpoint GET /api/v1/gateway/requests to list API request logs with pagination and filtering | | |
| TASK-036 | Implement request log endpoint with filters: status_code, response_time_min/max, endpoint_path, date_range | | |
| TASK-037 | Create endpoint GET /api/v1/gateway/rate-limits to show current rate limit status for authenticated API key | | |
| TASK-038 | Implement rate limit status with fields: limit, remaining, reset_at, requests_today, requests_this_hour | | |
| TASK-039 | Create analytics service in `packages/api-gateway/src/Services/AnalyticsService.php` with methods: getUsageStats, getEndpointStats, getErrorAnalysis, getPerformanceStats | | |
| TASK-040 | Implement error analysis: 4xx errors by type, 5xx errors with stack traces (in development only) | | |
| TASK-041 | Implement performance analysis: slowest endpoints, slowest requests, P50/P95/P99 response times | | |
| TASK-042 | Create analytics resources in `packages/api-gateway/src/Http/Resources/` for transforming models to JSON | | |
| TASK-043 | Write feature tests for analytics endpoints: access control, filtering, aggregation accuracy (9 tests) | | |

### GOAL-004: Implement Monitoring Dashboard

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-API-002 | Real-time API monitoring for administrators | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-044 | Create monitoring controller in `packages/api-gateway/src/Http/Controllers/MonitoringController.php` | | |
| TASK-045 | Create monitoring dashboard view in `packages/api-gateway/resources/views/monitoring/dashboard.blade.php` | | |
| TASK-046 | Implement real-time metrics display: requests/min, error rate, avg response time, p95 latency | | |
| TASK-047 | Create endpoint-wise breakdown showing top 10 endpoints by request count, errors, response time | | |
| TASK-048 | Implement tenant-wise breakdown for multi-tenant admins showing usage per tenant | | |
| TASK-049 | Add API key performance ranking: top/worst API keys by request count, error rate, response time | | |
| TASK-050 | Implement historical trend charts: requests/day, error rate/day, avg response time/day for past 30 days | | |
| TASK-051 | Create health status indicator showing API gateway status: healthy (green), degraded (yellow), down (red) | | |
| TASK-052 | Add alerts section showing recent rate limit exceeded events, high error rates, slow endpoints | | |
| TASK-053 | Implement export functionality: download usage report as CSV/PDF for date range | | |
| TASK-054 | Create dashboard controller returning JSON for dynamic updates via AJAX or WebSocket | | |
| TASK-055 | Write feature tests for monitoring dashboard: page rendering, data accuracy, access control (6 tests) | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|---|---|---|---|
| SR-API-001, DR-API-001, DR-API-002 | Comprehensive testing for rate limiting and metrics | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-056 | Write unit tests for RateLimiterService: window management, tier enforcement, counter operations (10 tests) | | |
| TASK-057 | Write unit tests for MetricsAggregationService: hourly/daily aggregation, stats calculation, edge cases (8 tests) | | |
| TASK-058 | Write unit tests for data sanitization: password redaction, token masking, API key hiding (6 tests) | | |
| TASK-059 | Write feature tests for rate limiting: tiered limits, multiple keys, tenant-wide limits, 429 response (12 tests) | | |
| TASK-060 | Write feature tests for analytics endpoints: filtering, pagination, aggregation accuracy (9 tests) | | |
| TASK-061 | Write feature tests for monitoring dashboard: real-time updates, trend data, alerts display (6 tests) | | |
| TASK-062 | Write integration tests: rate limiting with distributed systems, Redis failover, log queue processing (5 tests) | | |
| TASK-063 | Write performance tests: rate limit check < 1ms, logging < 5ms, metrics aggregation for 10k+ requests (3 tests) | | |
| TASK-064 | Achieve minimum 80% code coverage: run `./vendor/bin/pest --coverage` for rate limiting and analytics | | |
| TASK-065 | Create rate limiting guide in `packages/api-gateway/docs/RATE_LIMITING.md`: tier definitions, calculation methods, examples | | |
| TASK-066 | Create analytics guide in `packages/api-gateway/docs/ANALYTICS.md`: API endpoints, filtering, usage reports | | |
| TASK-067 | Create monitoring guide in `packages/api-gateway/docs/MONITORING.md`: dashboard usage, alerts, troubleshooting | | |
| TASK-068 | Create setup guide for analytics: Redis configuration, queue setup, scheduler configuration | | |
| TASK-069 | Validate acceptance criteria: rate limiting enforced, metrics accurate, dashboard functional, performance targets met | | |
| TASK-070 | Conduct code review: PSR-12 compliance, strict types, security review (rate limiting bypass attempts), performance review | | |
| TASK-071 | Run full test suite: `./vendor/bin/pest packages/api-gateway/tests/` verify all tests pass | | |

## 3. Alternatives

- **ALT-001**: Token bucket algorithm instead of sliding window - chosen for fairness and smooth rate limiting
- **ALT-002**: Synchronous logging instead of async - rejected due to 5ms latency constraint
- **ALT-003**: Database-only metrics instead of Redis - rejected due to performance requirements for 10k+ requests/second

## 4. Dependencies

- **DEP-001**: PLAN01 - API Gateway Foundation must be completed first (API keys, authentication)
- **DEP-002**: PLAN02 - API Documentation (optional but recommended for complete API documentation)
- **DEP-003**: Redis 6+ for rate limiting and metrics caching
- **DEP-004**: Laravel Queue configured and running (database or Redis driver)
- **DEP-005**: Laravel Scheduler running for metrics aggregation

## 5. Files

**Created/Modified Files:**

- **packages/api-gateway/src/Services/RateLimiterService.php**: Tiered rate limiting implementation
- **packages/api-gateway/src/Services/ApiRequestLoggingService.php**: Request logging service
- **packages/api-gateway/src/Services/MetricsAggregationService.php**: Metrics aggregation
- **packages/api-gateway/src/Services/AnalyticsService.php**: Analytics computations
- **packages/api-gateway/src/Enums/RateLimitTier.php**: Rate limit tier definitions
- **packages/api-gateway/src/Models/ApiRequestLog.php**: Request log model
- **packages/api-gateway/src/Models/ApiUsageMetric.php**: Usage metrics model
- **packages/api-gateway/src/Repositories/RateLimitRepository.php**: Redis rate limit operations
- **packages/api-gateway/src/Repositories/MetricsRepository.php**: Metrics data access
- **packages/api-gateway/src/Http/Controllers/AnalyticsController.php**: Analytics API endpoints
- **packages/api-gateway/src/Http/Controllers/MonitoringController.php**: Monitoring dashboard
- **packages/api-gateway/src/Http/Middleware/EnforceRateLimit.php**: Rate limiting middleware
- **packages/api-gateway/src/Http/Middleware/LogApiRequest.php**: Request logging middleware
- **packages/api-gateway/src/Http/Resources/UsageMetricResource.php**: API resources
- **packages/api-gateway/src/Http/Resources/RequestLogResource.php**: API resources
- **packages/api-gateway/src/Events/RateLimitExceededEvent.php**: Event class
- **packages/api-gateway/src/Listeners/LogRateLimitExceededListener.php**: Event listener
- **app/Jobs/LogApiRequestJob.php**: Async logging job
- **app/Jobs/AggregateApiMetricsJob.php**: Metrics aggregation job
- **packages/api-gateway/database/migrations/xxxx_add_metrics_columns_to_api_request_log.php**: Migration
- **packages/api-gateway/resources/views/monitoring/dashboard.blade.php**: Monitoring dashboard view
- **packages/api-gateway/docs/RATE_LIMITING.md**: Rate limiting documentation
- **packages/api-gateway/docs/ANALYTICS.md**: Analytics guide
- **packages/api-gateway/docs/MONITORING.md**: Monitoring guide
- **packages/api-gateway/tests/Unit/Services/RateLimiterServiceTest.php**: Unit tests
- **packages/api-gateway/tests/Unit/Services/MetricsAggregationServiceTest.php**: Unit tests
- **packages/api-gateway/tests/Feature/RateLimitingTest.php**: Feature tests
- **packages/api-gateway/tests/Feature/AnalyticsEndpointsTest.php**: Feature tests
- **packages/api-gateway/tests/Feature/MonitoringDashboardTest.php**: Feature tests

## 6. Testing

**Unit Tests (32 tests):**
- Rate limiter: sliding window, token bucket, counter operations, tier management
- Metrics aggregation: hourly/daily stats, calculations, edge cases
- Data sanitization: password redaction, token masking

**Feature Tests (27 tests):**
- Rate limiting: tiered enforcement, multiple keys, tenant-wide limits
- Analytics: filtering, pagination, aggregation
- Monitoring: dashboard rendering, real-time data

**Integration Tests (5 tests):**
- Distributed rate limiting with Redis
- Queue processing for async logging
- Metrics calculation accuracy

**Performance Tests (3 tests):**
- Rate limit check latency < 1ms
- Request logging latency < 5ms
- Metrics aggregation for 10k+ requests

**Total: 67 tests** with minimum 80% code coverage

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Redis rate limiter race conditions with high concurrency - Mitigation: use atomic Redis operations (INCR, EXPIRE)
- **RISK-002**: Metrics aggregation delay causing data inconsistency - Mitigation: implement eventual consistency with queue retry
- **RISK-003**: Sensitive data leakage in logs - Mitigation: comprehensive sanitization, access control, encryption at rest

**Assumptions:**
- **ASSUMPTION-001**: Redis is available and properly configured
- **ASSUMPTION-002**: Queue workers running and processing jobs
- **ASSUMPTION-003**: Scheduler is running for periodic aggregation
- **ASSUMPTION-004**: API keys properly created before rate limiting can be tested

## 8. KIV for future implementations

- **KIV-001**: GraphQL rate limiting with operation complexity scoring
- **KIV-002**: Machine learning anomaly detection for API usage patterns
- **KIV-003**: Cost-based rate limiting (charge by operation complexity not just requests)
- **KIV-004**: Webhook notifications for rate limit exceeded and quota warnings
- **KIV-005**: Advanced analytics: geographic distribution, device types, API endpoints heatmaps
- **KIV-006**: Billing integration using metrics for SaaS pricing model

## 9. Related PRD / Further Reading

- **PRD01-SUB23**: [API Gateway & Documentation](../prd/prd-01/PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md)
- **PRD01-SUB03**: [Audit Logging](../prd/prd-01/PRD01-SUB03-AUDIT-LOGGING.md) - For request logging patterns
- **Token Bucket Algorithm**: https://en.wikipedia.org/wiki/Token_bucket
- **Sliding Window Rate Limiting**: https://en.wikipedia.org/wiki/Rate_limiting
- **Redis Rate Limiting**: https://redis.io/commands/incr (Atomic operations)

---

**Implementation Status:** Ready for development

**Estimated Effort:** 3-4 weeks (1 developer)

**Previous Plan:** PRD01-SUB23-PLAN02 (API Documentation & Sandbox)

**Next Plan:** PRD01-SUB23-PLAN04 (Batch Operations & Webhooks)
