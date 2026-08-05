# Tupay Ledger & Settlement Engine

A production-grade backend for cross-border financial transactions (NGN ↔ CNY) with double-entry ledger accounting, step-up 2FA, distributed locking, and race-condition-safe concurrency.

## Quick Start (Docker)

```bash
docker-compose up
```

Services available at:
- **App**: http://localhost:8000
- **Swagger API Docs**: http://localhost:8000/docs
- **phpMyAdmin**: http://localhost:8080 (root/root)

All migrations and seeds run automatically on startup.

## Architecture Overview

This project implements a **Pragmatic Modular Monolith** with vertical slice development. Each slice is a complete feature with its own domain logic, services, and actions.

### Vertical Slices

1. **Auth Slice**: User login + step-up 2FA with TOTP and EAT tokens
2. **Foundation Slice**: Double-entry ledger, Money ValueObject, database schema
3. **Swap Engine**: Core swap logic with pessimistic locking and distributed lock safety
4. **Settlement**: Webhook-based settlement notifications and transaction finalization
5. **Documentation**: Comprehensive API docs via Swagger

### Key Design Decisions

- **No Floats**: All currency amounts use BIGINT subunits (NGN in kobo, CNY in fen) to ensure exactness
- **Modular Services + Actions**: Services orchestrate, Actions execute single responsibilities
- **Pessimistic Locking**: SELECT...FOR UPDATE at REPEATABLE READ isolation
- **Distributed Locks**: Redis-backed sorted key ordering prevents deadlocks
- **SWR Caching**: Exchange rates cached with stale-while-revalidate pattern (60s fresh, 300s stale)
- **Signature Verification**: Webhook payloads validated with HMAC-SHA256

## System Requirements

- **PHP**: 8.2+
- **Framework**: Laravel 10
- **Database**: MariaDB 10.4+ / MySQL 8.0+
- **Cache**: Redis
- **Quality**: PHPStan Level 7 + Laravel Pint

## Installation & Setup

```bash
git clone <repo>
cd tupay_ledger_task
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan l5-swagger:generate
```

### Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tupay_ledger
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SETTLEMENT_WEBHOOK_SECRET=your-secret-key
L5_SWAGGER_GENERATE_ALWAYS=true
```

## API Documentation

OpenAPI/Swagger docs available at: `GET /docs`

### Authentication

- Login: `POST /api/login` → Returns bearer token
- 2FA Challenge: `POST /api/2fa/challenge` (with TOTP code) → Returns EAT token
- Swap: `POST /api/swap` (with EAT token in `X-Elevated-Action-Token` header)

### Endpoints

#### Login
```json
POST /api/login
{
  "email": "user@example.com",
  "password": "password123"
}
```

#### 2FA Challenge
```json
POST /api/2fa/challenge
Headers: Authorization: Bearer <sanctum_token>
{
  "totp_code": "123456",
  "action_payload": {
    "source_currency": "NGN",
    "destination_currency": "CNY",
    "amount": 1000000
  }
}
```

#### Swap
```json
POST /api/swap
Headers: 
  Authorization: Bearer <sanctum_token>
  X-Elevated-Action-Token: <eat_token>
{
  "source_currency": "NGN",
  "destination_currency": "CNY",
  "amount": 1000000
}
```

#### Ledger History
```json
GET /api/ledger/{wallet_id}?page=1&per_page=50
Headers: Authorization: Bearer <sanctum_token>

Response:
{
  "wallet_id": 1,
  "currency": "NGN",
  "balance": 500000,
  "entries": [
    {
      "id": 1,
      "type": "debit",
      "amount": 1000000,
      "reference_id": "swap-123",
      "created_at": "2026-08-05T12:00:00Z"
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 50,
    "current_page": 1,
    "last_page": 2
  }
}
```

#### Settlement Webhook
```json
POST /api/webhook/settlement
{
  "transaction_id": 1,
  "status": "completed",
  "settled_at": "2026-08-05T12:00:00Z",
  "signature": "hmac_sha256_signature"
}
```

## Core Concepts

### Double-Entry Ledger

Every transaction creates a balanced pair of ledger entries:
- **Debit**: Amount removed from source wallet
- **Credit**: Amount added to destination wallet

Both use the same `reference_id` to maintain integrity.

```php
// Example: Swap 1M NGN → ~600k CNY
LedgerEntry::create([
    'wallet_id' => ngn_wallet_id,
    'type' => 'debit',
    'amount' => 1000000,
    'reference_id' => 'swap-123'
]);

LedgerEntry::create([
    'wallet_id' => cny_wallet_id,
    'type' => 'credit',
    'amount' => 597000,  // After slippage
    'reference_id' => 'swap-123'
]);
```

### Money ValueObject

All amounts use the `Money` class to prevent float arithmetic:

```php
$amount = Money::fromSubunits(1000000);  // 1M kobo = ₦10,000
$balance = $amount->getAmount();          // 1000000
$addMore = $amount->add(Money::fromSubunits(500000));
```

### Step-Up 2FA with EAT Tokens

1. User logs in → Sanctum bearer token
2. User provides TOTP code + action payload → EAT token (60s TTL, Redis-backed)
3. User calls swap endpoint with EAT token → Swap executes
4. EAT token is single-use (deleted after consumption)

### Distributed Locking

Prevents simultaneous swaps on the same wallets:

```php
$lockKeys = [
    (string) $user->id,
    (string) $sourceWallet->id,
    (string) $destinationWallet->id,
];
sort($lockKeys);  // Deterministic ordering prevents deadlocks

$this->lockService->withLock($lockKeys, function () {
    // Pessimistic row-level locking
    $sourceWallet->lockForUpdate()->first();
    $destinationWallet->lockForUpdate()->first();
    
    // Swap logic
});
```

### Exchange Rates & Slippage

- Base rate fetched from external API (cached with SWR)
- Slippage calculation:
  - Base: 0.5%
  - Tier: +0.1% per 500k NGN above 1M threshold
  - Example: 1.5M NGN swap → 0.6% slippage

### Database Schema

#### Users
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    email VARCHAR UNIQUE,
    password VARCHAR,
    totp_secret VARCHAR,
    created_at TIMESTAMP
);
```

#### Wallets
```sql
CREATE TABLE wallets (
    id BIGINT PRIMARY KEY,
    user_id BIGINT REFERENCES users,
    currency VARCHAR(3),  -- NGN, CNY
    created_at TIMESTAMP,
    UNIQUE(user_id, currency)
);
```

#### Ledger Entries
```sql
CREATE TABLE ledger_entries (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT REFERENCES wallets,
    type VARCHAR(6),  -- debit, credit
    amount BIGINT,
    reference_id VARCHAR,
    created_at TIMESTAMP,
    INDEX(wallet_id, created_at)
);
```

**Trigger**: `prevent_negative_wallet_balance` enforces balance constraints at the database level.

#### Transactions
```sql
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT REFERENCES users,
    source_wallet_id BIGINT REFERENCES wallets,
    destination_wallet_id BIGINT REFERENCES wallets,
    status VARCHAR(20),  -- pending, completed, failed, reversed
    source_amount BIGINT,
    destination_amount BIGINT,
    reference_id VARCHAR UNIQUE,
    settled_at TIMESTAMP NULL,
    created_at TIMESTAMP
);
```

#### EAT Tokens
```sql
CREATE TABLE eat_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT REFERENCES users,
    token VARCHAR UNIQUE,
    action_hash VARCHAR,
    expires_at TIMESTAMP,
    consumed_at TIMESTAMP NULL
);
```

### Database Indexing Strategy

Fast pagination on `GET /api/ledger/{wallet_id}` requires efficient filtering and sorting:

- **Composite Index**: `ledger_entries(wallet_id, created_at)`
  - Filters by wallet_id first (equality predicate)
  - Orders by created_at within the wallet (range scan)
  - Enables index-only scans for pagination without table lookups
  - Reduces query time from O(n) to O(log n) for large ledgers

- **Transaction Indexes**: `transactions(user_id, status)` + `transactions(created_at)`
  - User lookups (settlements) filtered by user and status
  - Time-range queries for audit logs and reporting

Without these indexes, paginating 1M+ ledger entries would require full table scans, violating concurrency guarantees during high-frequency swaps.

### Concurrency Safety

**Problem**: Multiple simultaneous swaps could overdraw a wallet.

**Solution**:
1. **Distributed Lock** (Redis): Sorted keys prevent deadlock
2. **Pessimistic Locking** (Database): `SELECT...FOR UPDATE` at REPEATABLE READ
3. **Trigger Constraint** (Database): Final guard against negative balance
4. **EAT Token Validation**: Prevents replay attacks

**Test**: Concurrent swap test with 10 sequential requests proves locking behavior.

## Testing

### Run All Tests
```bash
php artisan test
```

### Test Structure
- **RefreshDatabase trait**: Each test gets a clean SQLite in-memory DB
- **Redis mocking**: Mockery stubs Redis calls for auth & settlement tests
- **Integration tests**: Hit real endpoints with HTTP requests

### Test Coverage (19 tests passing)
- **Auth**: Login validation, TOTP verification, EAT token lifecycle (5 tests)
- **Swap**: Valid/invalid swaps, insufficient balance, EAT token validation, concurrency (3 tests)
- **Settlement**: Webhook signature verification, transaction status updates (4 tests)

## Quality Assurance

### PHPStan (Level 7)
```bash
./vendor/bin/phpstan analyse --memory-limit=512M
```

### Laravel Pint (Code Style)
```bash
./vendor/bin/pint
```

## Production Considerations

### MySQL Compatibility
- Isolation level syntax (`SET TRANSACTION ISOLATION LEVEL REPEATABLE READ`) only runs on MySQL
- SQLite uses implicit SERIALIZABLE isolation for tests

### Redis Requirements
- EAT tokens require Redis for TTL and atomic get/delete
- In production, use a managed Redis instance (AWS ElastiCache, etc.)

### Rate Caching
- Exchange rates cached for 60s (fresh), reused for 300s (stale)
- Implement actual rate API integration with provider (e.g., Wise, OFX)

### Settlement Integration
- Webhook signature uses HMAC-SHA256
- Implement retry logic + dead-letter queue for failed settlements
- Log all settlement events for audit trail

### Monitoring
- Log all swap operations with outcome (success/fail/lock timeout)
- Alert on negative balance violations (should never happen)
- Track EAT token consumption patterns for fraud detection

## Project Structure

```
app/
  Domains/
    Auth/
      Services/
      Actions/
      Controllers/
      DTOs/
    Ledger/
      Services/
      Actions/
    Swap/
      Services/
      Actions/
      Controllers/
    Settlement/
      Services/
      Actions/
      Controllers/
    ExchangeRate/
      Services/
  Models/
  Shared/
    Services/
    ValueObjects/
    Enums/
database/
  migrations/
  seeders/
tests/
  Integration/
    Auth/
    Swap/
    Settlement/
routes/
  api.php
config/
  settlement.php
```

## Common Tasks

### Add a New Currency
1. Update `Currency` enum in `app/Support/Enums/Currency.php`
2. Create wallet seeder for test users
3. Update swap rate service with new rate

### Adjust Slippage Tiers
Edit constants in `CalculateSwapAction`:
```php
private const SLIPPAGE_BASE = 0.005;        // 0.5%
private const SLIPPAGE_TIER = 0.001;        // 0.1% per tier
private const SLIPPAGE_THRESHOLD = 100000000; // 1M NGN
```

## Troubleshooting

### Swagger docs won't load
Run `php artisan l5-swagger:generate` after code changes.

### EAT token not working
- Verify TOTP code is correct (30s window)
- Check action payload matches exactly (hash mismatch fails)
- Ensure token hasn't been consumed yet

### Redis connection errors in production
Ensure Redis is running and accessible at configured host/port.

## License

Proprietary - Tupay Ledger Engine