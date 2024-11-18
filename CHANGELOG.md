After version 1.5.1, the nomenclature will be as follows:

X.Y.Z
- X: major
- Y: Nextcloud version
- Z: minor or patch

-----------------------------------------
1.30.2
- Update dependencies

1.29.3
- Update dependencies

1.28.4
- Update dependencies

1.30.1
- Update dependencies

1.29.2
- Update dependencies

1.28.3
- Update dependencies

1.30.0
- Added End-User IP forwarding to OpenOTP for Per-Network policies
- Update dependencies

1.29.1
- Added End-User IP forwarding to OpenOTP for Per-Network policies
- Update dependencies

1.28.2
- Added End-User IP forwarding to OpenOTP for Per-Network policies
- Update dependencies

1.29.0
- Domain setting and 'Default' domain value sent to OpenOTP has been removed. It must be now configured by WebADM Client Policy

1.28.1
- Domain setting and 'Default' domain value sent to OpenOTP has been removed. It must be now configured by WebADM Client Policy

1.28.0
- Change: Complete rebuild of the application and change of versioning
- Change: Voice authentication via web browser removed
- Change: Users cannot disable OpenOTP functionality

1.5.1
- Compatibility with versions supported by Nextcloud (cf. https://github.com/nextcloud/server/wiki/Maintenance-and-Release-Schedule)

1.5.0
- Add API Key

1.4.7
- Issue with signature
- Compatibility with v24 and v25 only

1.4.6
- Add compatibility for NextCloud v25
- Documentation URL updated

1.4.5
- Add dependencies

1.4.4
- Add compatibility for NextCloud v24

1.4.3
- Remove checks of SOAP extension and WSDL file
- Reintroduce the code to deal with CSP to avoid occasionnal
-   issues

1.4.2
- Fix some users not being able to administer their Two-factor
- Remove now useless setting to ignore SSL/TLS certificate errors
- Drop support for Nextcloud versions lower than 21
- Remove depreciation and improve performance

1.4.1
- Switch from SoapClient to NuSOAP
- Fix broken challenge authentication

1.4.0
- Add a setting to allow disabling OpenOTP authentication for local users

1.3.0
- Add a 2nd OpenOTP server URL in settings to be used as a failover if the first one is not responding
- Ignoring SSL/TLS certificate errors now allows to use an IP address instead of a domain name

1.2.0
- Add compatibility for NextCloud v23

1.1.0
- Add compatibility from NextCloud v16 to NextCloud v22
- Add support for Voice Biometrics authentication
- Add a setting to ignore SSL/TLS certificate errors
- Fix FIDO / FIDO2 for Safari

1.0.5

1.0.4-1
- Fixed Add all users to IRegistry when saving plugin configuration

1.0.4
- Add Statefull state to IRegistry: https://github.com/nextcloud/server/pull/9632
-  Admins can enable or disable 2FA for all users, this change give the possibility to be "statefull" in other word
-    we have to register enable/disable state for all users in IRegistry during plugin configuration (all user IRegistry will be populated at first config)
- FIDO2 now supported, All FIDO protocols fully supported:
- -  U2F: OpenOTP operated with CTAP1 only
- -  FIDO2: OpenOTP operates with CTAP2 only (WebAuthn)
- Activate Multilingual
- Add type to IProvider implemented classes

1.0.3
- Update register settings scripts
- Update deprecated classes

1.0.2
- app:check-code integrity
- Create new Administration menu entry in left side
- Personal settings are now situated in Security Section

1.0.1
- Add compatibility to NextCloud v12/13
- OC_User::getLogoutAttribute() is now deprecated
- Fixed ajax-loader img not showing while pressing Test button
- custom_csp in config deprecated - nonce used instead + addDefaultPolicy
- add Annotation @UseSession to store session
- add EventListener on DOMContentLoaded in template challenge
- implement contextual authentication

1.0.0
- Initial public release.
