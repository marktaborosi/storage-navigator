# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| 3.x | Yes |
| 2.x | No. Version 2 lets clients read files outside the configured root; upgrade to 3.x. |

## Reporting a vulnerability

Please do not open a public issue. Report it privately through
[GitHub's security advisory form](https://github.com/marktaborosi/storage-navigator/security/advisories/new),
or by email to mark.taborosi@gmail.com.

Include the version, your configuration (storage, enabled features), and the steps or request that
reproduce the problem. You will get an answer within 7 days. Fixes are released as a patch version
and announced in a GitHub security advisory, crediting you unless you prefer otherwise.

## Scope

In scope: anything that lets a client read, list, change or delete data the configuration should
hide or protect, run script in the page, or bypass a disabled feature, a filter, the `authorize`
callback or the CSRF protection.

Out of scope: authentication itself (the navigator expects your application to log users in),
and files your web server serves directly because the browsed folder is inside the document root.
