# BHWIS activation integration

The resident activation integration is shipped in standby mode. Project ACCESS does not create or supervise the SSH reverse tunnel; the tunnel must expose the remote SQL Server on the host and port configured below.

## Enablement

1. Create a read-only SQL Server login restricted to the seven supported BHWIS tables.
2. Establish and supervise the reverse tunnel outside the Laravel application.
3. Configure `BHWIS_HOST`, `BHWIS_PORT`, `BHWIS_DATABASE`, `BHWIS_USERNAME`, and `BHWIS_PASSWORD`.
4. Set the applicable legal document versions and leave `BHWIS_ENABLED=false`.
5. Run `php artisan config:clear`, temporarily enable the integration, and run `php artisan bhwis:check`.
6. After the connection and schema check succeeds, keep `BHWIS_ENABLED=true` and rebuild the production configuration cache if one is used.

`bhwis:check` is read-only. It verifies that these tables and their activation-critical columns are visible: `tblPersonalInfo`, `tblFamilyMembers`, `tblBHWMaster`, `tblBarangay`, `tblCivilStatus`, `tblSourceIncomeType`, and `tblEduc_Attainment`.

## Runtime behavior

Existing local PINs never contact BHWIS. A PIN that is absent locally is looked up only after all three activation acknowledgments are accepted. Unavailable or disabled BHWIS connectivity returns a retryable response and does not create partial resident, household, reference, or BHW assignment data.

Activation attempts are rate-limited by IP address and normalized PIN. Operational logs contain the consent attempt UUID and exception class, not MPINs or database credentials.
