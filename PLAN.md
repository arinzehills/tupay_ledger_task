# Tupay Ledger & Settlement Engine - Implementation Plan

## Summary
Production-grade cross-border financial backend (NGN ↔ CNY) with bank-grade double-entry ledger, step-up 2FA security, and concurrent swap operations.

## Expected Outcome
- ✅ API endpoints: login, 2fa challenge, swap execution, settlement webhooks, ledger history
- ✅ Pure double-entry ledger with BIGINT subunit precision (no floats)
- ✅ Step-up 2FA with Elevated Action Tokens (EAT) - 60-second TTL, single-use
- ✅ Distributed locking to prevent race conditions (Redis + deterministic lock order)
- ✅ Concurrent integration tests proving only 1 of 10 simultaneous swaps succeeds
- ✅ Comprehensive README documenting architecture, security patterns, and indexing strategy

---

## Vertical Slices (Feature-by-Feature)

### Slice 1: AUTH SYSTEM (Login + 2FA + EAT Tokens)
**Goal**: Complete user authentication with step-up 2FA flow

**Database:**
- [ ] Create users table migration (id, email, password, totp_secret, created_at)

**Models:**
- [ ] app/Models/User.php

**Support/Enums:**
- [ ] app/Support/Enums/TokenStatus.php (VALID, CONSUMED, EXPIRED)

**Auth Domain:**
- [ ] app/Domains/Auth/Services/TotpService.php (generate, verify TOTP codes)
- [ ] app/Domains/Auth/Services/EatService.php (issue, consume EAT tokens)
- [ ] app/Domains/Auth/Actions/IssueEatAction.php (create 60-sec token with action hash)
- [ ] app/Domains/Auth/Actions/ConsumeEatAction.php (atomically invalidate token)
- [ ] app/Domains/Auth/Controllers/AuthController.php
- [ ] app/Domains/Auth/DTOs/LoginDTO.php
- [ ] app/Domains/Auth/DTOs/ChallengeDTO.php

**Routes:**
- [ ] POST /api/login (email, password)
- [ ] POST /api/2fa/challenge (token, totp_code, action_payload)

**Tests:**
- [ ] tests/Integration/Auth/LoginTest.php
- [ ] tests/Integration/Auth/TwoFATest.php

**Database Seeder:**
- [ ] database/seeders/UserSeeder.php (test users with TOTP secrets)

---

### Slice 2: FOUNDATION (ValueObjects, Models, Migrations)
**Goal**: Set up core financial data structures

**Support/ValueObjects:**
- [ ] app/Shared/ValueObjects/Money.php (BIGINT subunit math only)

**Support/Enums:**
- [ ] app/Support/Enums/Currency.php (NGN, CNY)
- [ ] app/Support/Enums/TransactionStatus.php (PENDING, COMPLETED, FAILED)
- [ ] app/Support/Enums/LedgerEntryType.php (DEBIT, CREDIT)

**Database Migrations:**
- [ ] wallets table (user_id, currency, no balance column)
- [ ] ledger_entries table (wallet_id, type, amount, reference_id)
- [ ] transactions table (user_id, source_wallet_id, dest_wallet_id, status)
- [ ] eat_tokens table (user_id, token, action_hash, expires_at, consumed_at)

**Models:**
- [ ] app/Models/Wallet.php
- [ ] app/Models/LedgerEntry.php
- [ ] app/Models/Transaction.php
- [ ] app/Models/EatToken.php

---

### Slice 3: SWAP ENGINE (Core Financial Logic)
**Goal**: Currency swap with locking, ledger posting, rate calculation

**Shared/Services:**
- [ ] app/Shared/Services/DistributedLockService.php (Redis locks, sorted order)

**ExchangeRate Domain:**
- [ ] app/Domains/ExchangeRate/Services/RateService.php (SWR cache from external API)
- [ ] app/Domains/ExchangeRate/Clients/ExchangeRateClient.php (mock endpoint)

**Ledger Domain:**
- [ ] app/Domains/Ledger/Services/LedgerService.php (query, calculate balance)
- [ ] app/Domains/Ledger/Repositories/LedgerRepository.php (pagination with running balance)
- [ ] app/Domains/Ledger/Actions/PostLedgerEntriesAction.php (create balanced entries)

**Swap Domain:**
- [ ] app/Domains/Swap/Services/SwapService.php (orchestrates swap)
- [ ] app/Domains/Swap/Actions/ValidateBalanceAction.php (check sufficient funds)
- [ ] app/Domains/Swap/Actions/CalculateSwapAction.php (conversion + slippage)
- [ ] app/Domains/Swap/Actions/ExecuteSwapAction.php (locked transaction)
- [ ] app/Domains/Swap/Controllers/SwapController.php
- [ ] app/Domains/Swap/DTOs/SwapDTO.php

**Routes:**
- [ ] POST /api/swap (requires X-Elevated-Action-Token header)
- [ ] GET /api/ledger/{wallet_id} (paginated with running balance)

**Tests:**
- [ ] tests/Integration/Swap/SwapTest.php
- [ ] tests/Integration/Swap/ConcurrencyTest.php (10 concurrent swaps, only 1 succeeds)

---

### Slice 4: SETTLEMENT WEBHOOK
**Goal**: Idempotent webhook handling for third-party settlements

**Settlement Domain:**
- [ ] app/Domains/Settlement/Controllers/SettlementController.php
- [ ] app/Domains/Settlement/Middleware/VerifyWebhookSignature.php
- [ ] app/Domains/Settlement/Services/SettlementService.php
- [ ] app/Domains/Settlement/Jobs/ProcessSettlementJob.php

**Routes:**
- [ ] POST /api/webhooks/settlement (HMAC signature verification)

---

### Slice 5: DOCUMENTATION & TESTING
**Goal**: Complete test suite and comprehensive README

**Tests:**
- [ ] tests/Integration/Swap/StressTest.php (10 concurrent, 1 succeeds)

**Documentation:**
- [ ] CLAUDE.md (architecture decisions, patterns, security)
- [ ] README.md (API reference, setup, testing, indexing strategy)
- [ ] Postman collection or api-test.http

---

## Architecture Structure
```
app/
├── Domains/
│   ├── Auth/              (Login, 2FA, EAT)
│   ├── Wallet/            (Wallet queries)
│   ├── Ledger/            (Double-entry bookkeeping)
│   ├── Swap/              (Currency conversion)
│   ├── ExchangeRate/      (Rate fetching)
│   └── Settlement/        (Webhooks)
├── Support/
│   ├── Enums/             (Currency, Status)
│   ├── Exceptions/        (Custom errors)
│   └── Contracts/         (Interfaces)
├── Shared/
│   ├── Services/          (Locking, Rates)
│   └── ValueObjects/      (Money)
└── Models/                (Eloquent models)
```

---

## Next Steps
1. ✅ Create PLAN.md (this file)
2. → Start Slice 1: AUTH SYSTEM
3. Continue with other slices