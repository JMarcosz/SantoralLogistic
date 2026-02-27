# Fiscal Module Logging Documentation

## Overview

The fiscal module implements comprehensive structured logging for all fiscal operations including invoice generation, NCF assignment, and DGII exports. All fiscal logs are written to a dedicated log channel for easy filtering and monitoring.

---

## Log Channel

**Channel Name:** `fiscal`  
**Log File:** `storage/logs/fiscal.log`  
**Rotation:** Daily  
**Retention:** 90 days (for compliance)

---

## What Gets Logged

### Invoice Creation

When an invoice is successfully created from a pre-invoice:

**Log Level:** `INFO`

**Message:** `"Invoice created successfully"`

**Context:**

```json
{
    "invoice_id": 123,
    "invoice_number": "INV-2025-000123",
    "ncf": "B01-00000000045",
    "ncf_type": "B01",
    "customer_id": 45,
    "customer_name": "Empresa ABC SRL",
    "pre_invoice_id": 98,
    "pre_invoice_number": "PI-2025-000098",
    "total_amount": 11800.0,
    "currency_code": "DOP",
    "user_id": 5
}
```

---

### NCF Sequence Errors

#### No Fiscal Sequence Available

When attempting to generate an invoice but no NCF sequence is available:

**Log Level:** `WARNING`

**Message:** `"No fiscal sequence available for invoice generation"`

**Context:**

```json
{
    "ncf_type": "B01",
    "series": null,
    "pre_invoice_id": 98,
    "pre_invoice_number": "PI-2025-000098",
    "customer_id": 45,
    "customer_name": "Empresa ABC SRL",
    "user_id": 5
}
```

**Action Required:** Create new fiscal sequence for the specified NCF type and series.

---

#### Fiscal Sequence Exhausted

When a fiscal sequence has reached its limit:

**Log Level:** `WARNING`

**Message:** `"Fiscal sequence exhausted"`

**Context:**

```json
{
    "sequence_id": 3,
    "ncf_type": "B01",
    "series": null,
    "current_ncf": "B01-00001000000",
    "ncf_to": "B01-00001000000",
    "pre_invoice_id": 98,
    "pre_invoice_number": "PI-2025-000098",
    "customer_id": 45,
    "user_id": 5
}
```

**Action Required:** Create new fiscal sequence with fresh NCF range.

---

### DGII Exports

#### 607 Report (Sales/Income)

When a 607 report is generated:

**Log Level:** `INFO`

**Message:** `"DGII 607 report generated"`

**Context:**

```json
{
    "period_start": "2025-01-01",
    "period_end": "2025-01-31",
    "invoice_count": 234,
    "total_amount": 5678900.0,
    "user_id": 5
}
```

---

#### 608 Report (Cancelled Invoices)

When a 608 report is generated:

**Log Level:** `INFO`

**Message:** `"DGII 608 report generated"`

**Context:**

```json
{
    "period_start": "2025-01-01",
    "period_end": "2025-01-31",
    "cancelled_invoice_count": 12,
    "user_id": 5
}
```

---

## How to Filter Fiscal Logs

### View All Fiscal Logs

```bash
tail -f storage/logs/fiscal.log
```

### View Only Warnings and Errors

```bash
grep -E "WARNING|ERROR" storage/logs/fiscal.log
```

### View NCF Sequence Issues

```bash
grep -E "No fiscal sequence|Fiscal sequence exhausted" storage/logs/fiscal.log
```

### View Invoice Creation Logs

```bash
grep "Invoice created successfully" storage/logs/fiscal.log
```

### View DGII Export Logs

```bash
grep -E "DGII 607|DGII 608" storage/logs/fiscal.log
```

### View Logs for Specific Customer

```bash
grep '"customer_id":45' storage/logs/fiscal.log
```

### View Logs for Specific User

```bash
grep '"user_id":5' storage/logs/fiscal.log
```

### View Logs for Specific Date Range

```bash
# View logs from January 2025
grep "2025-01" storage/logs/fiscal-2025-01-*.log
```

---

## Troubleshooting Guide

### Issue: Invoices Not Being Created

**Symptoms:**

- Users report inability to generate fiscal invoices
- Error messages about NCF unavailability

**Steps:**

1. **Check for NCF sequence warnings:**

    ```bash
    tail -100 storage/logs/fiscal.log | grep WARNING
    ```

2. **If "No fiscal sequence available":**
    - Verify fiscal sequences exist for the NCF type
    - Check that sequences are active
    - Verify `valid_from` and `valid_to` dates cover current date
    - Create new sequence if needed

3. **If "Fiscal sequence exhausted":**
    - Identify the exhausted sequence ID from logs
    - Create new fiscal sequence with next range
    - Mark old sequence as inactive

---

### Issue: DGII Export Missing Invoices

**Symptoms:**

- 607/608 reports don't include all expected invoices
- Invoice counts seem low

**Steps:**

1. **Check export logs:**

    ```bash
    grep "DGII 607 report generated" storage/logs/fiscal.log | tail -5
    ```

2. **Verify period and count:**
    - Compare `invoice_count` in logs with expected count
    - Verify `period_start` and `period_end` are correct

3. **Check invoice status:**
    - 607 only includes `STATUS_ISSUED` invoices
    - 608 only includes `STATUS_CANCELLED` invoices
    - Verify `issue_date` falls within period

4. **Check for validation errors:**
    - Look for warning logs about missing fiscal data
    - Ensure customers have valid `tax_id` and `tax_id_type`

---

### Issue: Duplicate NCFs

**Symptoms:**

- Multiple invoices with same NCF
- Database unique constraint errors

**Steps:**

1. **Check for concurrent invoice creation:**

    ```bash
    grep "Invoice created successfully" storage/logs/fiscal.log | grep -E "narrow_time_window"
    ```

2. **Verify fiscal sequence locking:**
    - Ensure `FiscalNumberService::getNextNcf` uses proper locking
    - Check for database deadlocks

3. **Investigate sequence overlap:**
    - Check if multiple sequences exist for same NCF type/series
    - Use `FiscalSequence::hasOverlap()` to detect issues

---

## Production Monitoring Recommendations

### Daily Checks

- Monitor for NCF exhaustion warnings
- Verify DGII exports are generated successfully
- Check invoice creation volume vs. historical baseline

### Weekly Checks

- Review NCF sequence consumption rate
- Plan for new sequences before exhaustion
- Audit fiscal log retention (should be 90 days)

### Alerting (Optional)

Consider setting up alerts for:

1. **NCF Exhaustion Warning**
    - Trigger: 3+ "Fiscal sequence exhausted" logs in 1 hour
    - Action: Create new fiscal sequence immediately

2. **No Sequence Available**
    - Trigger: Any "No fiscal sequence available" log
    - Action: Investigate and create sequence

3. **High Invoice Volume**
    - Trigger: Invoice creation rate > 2x normal
    - Action: Verify system health and NCF availability

---

## Log Retention Policy

**Retention Period:** 90 days

**Rotation:** Daily at midnight

**Location:** `storage/logs/fiscal-YYYY-MM-DD.log`

**Compliance Note:** Fiscal logs should be retained for at least 90 days to support audits and compliance requirements under Dominican Republic tax law.

---

## Integration with Monitoring Tools

### Sentry/Bugsnag (Optional)

If using error tracking, fiscal exceptions are automatically captured with context:

```php
// In app/Exceptions/Handler.php
public function register(): void
{
    $this->reportable(function (NoFiscalSequenceAvailableException $e) {
        // Automatically sent to Sentry with context
    });

    $this->reportable(function (FiscalSequenceExhaustedException $e) {
        // Automatically sent to Sentry with context
    });
}
```

### Log Aggregation (Optional)

For production systems, consider shipping fiscal logs to:

- ELK Stack (Elasticsearch, Logstash, Kibana)
- Splunk
- Datadog
- CloudWatch

---

## Example Queries

### Count Invoices Created Today

```bash
grep "Invoice created successfully" storage/logs/fiscal-$(date +%Y-%m-%d).log | wc -l
```

### Get Total Amount Invoiced Today

```bash
grep "Invoice created successfully" storage/logs/fiscal-$(date +%Y-%m-%d).log | \
  grep -oP '"total_amount":\K[0-9.]+' | \
  awk '{s+=$1} END {print s}'
```

### List All NCF Types Used Today

```bash
grep "Invoice created successfully" storage/logs/fiscal-$(date +%Y-%m-%d).log | \
  grep -oP '"ncf_type":"\K[^"]+' | \
  sort | uniq -c
```

---

## Support

For issues or questions about fiscal logging:

1. Check this documentation first
2. Review recent fiscal logs for clues
3. Contact development team with:
    - Relevant log excerpts
    - Steps to reproduce
    - Expected vs. actual behavior
