# SaaS Production Launch Pack

## Production Deployment Checklist

1. Infrastructure
- Provision app nodes, managed MySQL, managed Redis, object storage, and backup bucket.
- Enable TLS (Let's Encrypt or managed cert).
- Configure domains: `app.yourdomain.ma`, optional `api.yourdomain.ma`.

2. Laravel runtime
- Set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.
- Set `QUEUE_CONNECTION=redis` and run dedicated workers.
- Set `QUEUE_AFTER_COMMIT=true`.
- Set `SESSION_DRIVER=redis`, `CACHE_STORE=redis` for scale.
- Set `SESSION_SECURE_COOKIE=true` and HTTPS forced at reverse proxy.

3. Database
- Run `php artisan migrate --force`.
- Seed base plans: `php artisan db:seed --class=SubscriptionPlanSeeder`.
- Create first support admin and tenant.
- Enable daily logical backup + weekly restore test.

4. Workers and scheduler
- Run queue workers with supervisor/systemd (min 2 workers).
- Run scheduler every minute.
- Add periodic `outbox:process` schedule.

5. Monitoring
- Alert on failed jobs count, queue lag, DB CPU, disk usage, error rate.
- Audit log retention policy (e.g. 12 months).

## Scaling Limits and Bottlenecks

- At 10 tenants: single app + single DB instance sufficient.
- At 100 tenants: Redis/session+cache mandatory, queue workers >= 3.
- At 1000 tenants: DB read/write separation, partition heavy tables (documents/audit/outbox), CDN for assets.

Likely first bottlenecks:
- Large dashboard aggregate queries.
- `f_docentete` and `audit_logs` growth.
- Synchronous PDF generation spikes.

## First Customer Launch Strategy

1. Closed beta (3-5 pilot SMBs)
- Onboard accounting-heavy + retail-heavy profiles.
- Validate invoice numbering/legal docs, Arabic/French wording.
- Weekly support loop and UX quick fixes.

2. Controlled paid rollout (10-20 tenants)
- Starter/Growth plans only.
- Manual assisted onboarding + demo data by default.
- SLA: business-hours support.

3. Scale phase
- Self-serve onboarding funnel.
- in-app upgrade/downgrade + automated billing webhooks.

## Infrastructure Cost Estimates (MAD/month)

### 10 tenants
- App VM small: 250-400
- Managed MySQL small: 350-600
- Redis small: 150-250
- Backups + storage + email: 100-250
- Total: **850-1,500 MAD/month**

### 100 tenants
- 2 app nodes + LB: 1,200-2,000
- Managed MySQL medium: 1,200-2,200
- Redis medium: 400-700
- Object storage/CDN/email/monitoring: 500-1,000
- Total: **3,300-5,900 MAD/month**

### 1000 tenants
- 4-8 app nodes + LB: 5,000-12,000
- Managed MySQL large/HA: 8,000-18,000
- Redis HA: 2,000-4,500
- Storage/CDN/monitoring/security/backups: 3,000-7,000
- Total: **18,000-41,500 MAD/month**

Notes:
- Assumes typical SMB document volumes, not enterprise spikes.
- Costs vary by provider and region (EU vs local hosting).
