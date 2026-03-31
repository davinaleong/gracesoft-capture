# GraceSoft Capture Data Inventory and Classification

Last Updated: 2026-03-31

## Classification Levels

- `restricted`: direct identifiers and free-text content with potential sensitive data
- `confidential`: operational metadata with potential linkage risk
- `internal`: non-sensitive product/runtime data

## Field Inventory

| Domain | Table/Flow | Field | Classification | Purpose | Retention/Minimization Notes |
| --- | --- | --- | --- | --- | --- |
| Form Submission | enquiries | name | restricted | identify enquirer | required for inbox communication context |
| Form Submission | enquiries | email | restricted | reply/contact channel | required for customer response |
| Form Submission | enquiries | subject | restricted | triage context | required for workflow prioritization |
| Form Submission | enquiries | message | restricted | enquiry content | required for case handling |
| Form Submission | enquiries | metadata.ip_address | confidential | abuse/rate-limiting evidence | disabled by default via `CAPTURE_STORE_SUBMISSION_REQUEST_METADATA=false` |
| Form Submission | enquiries | metadata.user_agent | confidential | abuse diagnostics | disabled by default via `CAPTURE_STORE_SUBMISSION_REQUEST_METADATA=false` |
| Collaboration | account_invitations | email | restricted | invitation delivery | limited to collaborator onboarding |
| Audit | audit_logs.metadata.* | mixed | confidential/restricted | traceability | sensitive keys redacted via `capture.features.audit_metadata_redact_keys` |
| Audit | data_access_logs.metadata.* | mixed | confidential/restricted | compliance evidence | sensitive keys redacted via `capture.features.audit_metadata_redact_keys` |
| Analytics to HQ | event payload | account_id, application_id, form_uuid, enquiry_uuid, status, occurred_at | internal/confidential | product telemetry | excludes raw PII fields (`name`, `email`, `subject`, `message`) |

## Minimization Controls Implemented

- Form submission request metadata (`ip_address`, `user_agent`) is opt-in and disabled by default.
- HQ analytics event payload excludes raw enquiry PII fields.
- Audit/data-access log metadata redacts configured sensitive keys recursively.
- Domain validation and redirect behavior are feature-flagged and optional.
