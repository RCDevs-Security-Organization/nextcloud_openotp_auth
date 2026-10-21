# Tests

Run `composer test` from the app directory after installing the development dependencies.
The app must be inside a Nextcloud 33 checkout: the bootstrap loads its class definitions,
but does not start Nextcloud or access the configured database.

The migration integration tests require PHP's `pdo_sqlite` extension. They execute the
Nextcloud QueryBuilder and SQLite conflict-handling adapter against an in-memory database
with a custom table prefix. Without this extension, these tests are skipped.

Coverage includes provider-state conflicts, unrelated providers, multiple batches,
transaction rollback, restart after partial completion, and repeated execution.
The unit suite also validates `appinfo/info.xml` against the Nextcloud checkout's
`resources/app-info.xsd`, including the required order of repair steps.

Administrator-policy tests cover both global flags for local, LDAP and other
backends, first-login initialization, existing and new registry entries, settings
save/retry, upgrade reconciliation, and preservation of other 2FA providers.
