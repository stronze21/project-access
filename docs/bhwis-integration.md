# BHWIS registration integration

Project Access uses BHWIS as an on-demand registration authority. It does not bulk-sync or periodically synchronize BHWIS records. CSV exports remain a supported manual fallback and initial-load path.

Both the ProjectAccessApp API and the PWA registration portal use this flow:

1. The resident submits a PIN, last name, and birth date.
2. Project Access performs one prepared BHWIS query matching all three values.
3. If no exact match exists, registration is rejected without creating a resident.
4. If the match is valid, Project Access retrieves the resident's BHWIS record and supported related records.
5. A missing local resident is imported and linked by BHWIS PIN. An existing local resident is not overwritten.
6. Project Access sets the resident's MPIN or password and completes registration.

## Production architecture

The Windows machine creates a reverse SSH tunnel from the Hostinger loopback endpoint `127.0.0.1:45133` to the local SQL Server endpoint `127.0.0.1:1433`. Laravel does not use its `sqlsrv` connection for BHWIS. `LocalPcDatabase` opens the configured ODBC DSN with PDO, and `BhwisRepository` owns all live BHWIS SQL.

Create this DSN in the Hostinger account's `.odbc.ini`:

```ini
[local_pc_sqlserver]
Description = Local SQL Server through reverse SSH tunnel
Driver = FreeTDS
Server = 127.0.0.1
Port = 45133
Database = BHWIS
TDS_Version = 7.4
ClientCharset = UTF-8
```

Set these application variables without committing credentials:

```env
BHWIS_ENABLED=true
DB_LOCAL_DSN="odbc:local_pc_sqlserver"
DB_LOCAL_DATABASE="BHWIS"
DB_LOCAL_USERNAME=
DB_LOCAL_PASSWORD=
DB_LOCAL_TIMEOUT=15
```

`BHWIS_ENABLED` controls registration lookups. The diagnostic command can test configuration before registration lookups are enabled. The DSN determines the database used by PDO ODBC and the diagnostic verifies it with `DB_NAME()`.

After deployment, rebuild Laravel configuration:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Diagnostics

```bash
php artisan bhwis:test-connection
php artisan bhwis:check
```

The test command prints only success/failure, database name, and server time. `bhwis:check` additionally verifies the registration-related tables and columns. Neither command imports residents.

Technical connection and registration-import failures are written to `storage/logs/bhwis-*.log`; credentials, DSNs, and personal records are not logged.

## CSV fallback

If the SSH tunnel, FreeTDS driver, DSN, or local SQL Server is unavailable, administrators can use **Residents > BHWIS Integration > Import Manager**. Upload `tblPersonalInfo.csv` alone or include the supported family, BHW, barangay, civil-status, income-source, and education exports. Uploading stages the files only. Review validation results, run a promotion preview, then explicitly confirm promotion. Existing CSV templates and duplicate/conflict safeguards remain supported.

## Troubleshooting

- `could not find driver`: confirm PHP PDO ODBC is enabled; `pdo_sqlsrv` is not used for BHWIS.
- DSN not found: confirm `.odbc.ini` belongs to the same Hostinger user that runs PHP and the DSN name is exactly `local_pc_sqlserver`.
- Login failure: verify the read-only SQL Server login in environment variables. Never paste credentials into logs or source files.
- Timeout/refused connection: verify the reverse SSH tunnel is listening on Hostinger `127.0.0.1:45133` and forwarding to Windows `127.0.0.1:1433`.
- Wrong database: run `php artisan bhwis:test-connection` and compare the returned database with `BHWIS`.
- Configuration changes not visible: run `php artisan optimize:clear`, then `php artisan config:cache`.

Use a read-only BHWIS login with access only to required tables. The temporary `/test-tunnel` diagnostic is restricted to authenticated system administrators and returns only the database name and server time. Remove it after production tunnel verification is complete.
