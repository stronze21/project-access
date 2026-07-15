# Legacy CSV integration

The legacy importer is intentionally split into staging and promotion. Staging preserves each source row and does not modify residents, households, or BHW assignments. Promotion is also a dry run unless `--commit` is supplied.

## Setup

Apply the additive schema changes before importing:

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## Validate and stage files

Validate one file without database writes:

```bash
php artisan legacy:import "D:\path\tblPersonalInfo.csv"
```

Stage the validated file:

```bash
php artisan legacy:import "D:\path\tblPersonalInfo.csv" --commit
```

Pass a directory to discover the seven canonical CSV filenames, or pass multiple explicit files. Do not supply both the original and synthetic versions of the same source table in one batch.

```bash
php artisan legacy:import "D:\path\legacy-export" --commit
```

An identical file manifest is idempotent and returns the existing batch instead of inserting duplicate staging rows.

## Review and promote

Run promotion in report-only mode first:

```bash
php artisan legacy:promote 1
```

After reviewing the created, matched, incomplete, and conflict counts:

```bash
php artisan legacy:promote 1 --commit
```

Promotion creates complete new records and fills only empty fields on exact existing matches. Populated canonical values are never overwritten; differences are recorded as conflicts.

### Two-stage household lifecycle

Promoting `tblPersonalInfo.csv` creates a provisional one-person household for each eligible resident with an address. The household is explicitly marked with `is_provisional` and `provisional_for_pin`, and its external key is `LEGACY-PIN-{PIN}`. A resident who already belongs to a non-provisional household is never moved by this step.

When `tblFamilyMembers.csv` is promoted later, its `FamilyNumber` becomes the permanent household key. Members are moved only from their own provisional household (or from no household) into that family household. Empty importer-created provisional households are soft-deleted only when they have no residents and no distributions. Residents assigned to multiple family numbers remain in their provisional households for review.

The family importer can reuse the latest valid staged personal address by PIN even when the personal and family files were uploaded as separate batches. If members have different addresses, the first available address in the family export is selected deterministically and the batch reports an `address_variations` count.

## Core mappings

- `tblPersonalInfo.PIN` → `residents.resident_id`
- `tblPersonalInfo.Date_Modified` → `residents.updated_at` for new imports; existing timestamps never move backwards
- Legacy family number → `households.household_id`
- Building registry number → `households.building_registry_number`
- Education code → `residents.educational_attainment`
- Income-source code → `residents.source_income_type_id`
- BHW `PIN`/`PIN2` → primary/secondary zone assignments

Promoting `tblBHWMaster.csv` synchronizes exactly two possible assignment slots per zone: `PIN` is the primary BHW and `PIN2` is the secondary BHW. The database prevents more than one assignment in either slot and prevents the same PIN from occupying both slots in one zone. Resolved residents receive `residents.is_bhw = true`; replacing or removing a slot clears the former resident's flag when they have no other BHW assignment.

The known civil-status, education, and income-source codebooks are built into promotion, so a populated `tblPersonalInfo.csv` can be processed without separately supplying those three reference CSVs. When reference files are supplied, their values take precedence.

Duplicate identifiers, missing required identity fields, residents assigned to multiple families, conflicting building registry numbers, blank BHW barangay codes, and BHW code `40` remain staged for review.

## Management pages

Users with the `manage-legacy-reference-data` permission can open **Residents -> Import / Export -> Manage Legacy Data** and maintain:

- Source income types
- Educational attainments
- Civil-status source labels and their project equivalents
- Legacy-to-project barangay mappings
- BHW zones with a maximum of one primary PIN and one secondary PIN

Active managed values are used by future promotions. A supplied reference CSV updates the managed values for its legacy codes during committed promotion. BHW changes immediately synchronize each assigned resident's `is_bhw` flag.

Residents created by committed legacy promotion receive `is_legacy_imported = true` and display an **Imported** badge in the resident list and profile. Residents that already existed before a matching import remain native records and are not relabeled.
