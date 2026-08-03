# Project instructions

## Mobile release hygiene

For every user-facing change under `ProjectAccessApp/ProjectAccessApp`:

- Keep `ApplicationDisplayVersion` and the monotonically increasing `ApplicationVersion` in `ProjectAccessApp/ProjectAccessApp/ProjectAccessApp.csproj` current. Do not version releases only through command-line overrides.
- Add the release and concise user-facing notes to `public/RELEASE_NOTES.md` in the same change. This file is publicly accessible, so never include secrets or internal-only information.
- When publishing an APK, use the version values from the project file, sign with the established production keystore, and verify the APK manifest and signing-certificate fingerprint before delivery.
- Keep the server's Admin > App Release version name, version code, release notes, and uploaded APK synchronized with the published build so update checks and the “What's New” dialog remain accurate.
- Never publish a lower or reused Android version code.
