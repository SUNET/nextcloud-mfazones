<!-- SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com> -->
<!-- SPDX-FileCopyrightText: SUNET <kano@sunet.se> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
# MFA Zones Nextcloud App

This is a Nextcloud app that enables file owners and administrators to restrict access to files and folders based on whether or not a logged-in user has passed MFA (multi-factor authentication) verification.

## Requirements

- Nextcloud 29 or later
- Two-factor authentication (2FA) enabled [see here for more info](https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/two_factor-auth.html)
- `Files Access Control` (Nextcloud app)
- `Files Automated Tagging` (Nextcloud app)
- More [optional external dependencies](https://github.com/pondersource/nextcloud-mfa-awareness#nextcloud-mfa-awareness), depending on your deployment (using SAML? using Global Site Selector?)

If you are using any single signon (SSO) solution, you will need to enable the `Step Up Auth` app. You can find it in the [Nextcloud app store](https://apps.nextcloud.com/apps/stepupauth).

## Security
Multi-factor authentication (MFA) is a method of authentication that requires a user to provide more than one piece of information to verify their identity.
The goal is to make sure that the user establishing a session, is really the actual person that the system thinks that it is. This is more about establishing
trust than it is about preventing unauthorized access. The MFA Zones app handles the trust part of the equation while [files_accesscontrol](https://github.com/nextcloud/files_accesscontrol) handles the access control part.
We also rely on a lot of other Nextcloud features, such as the MFA providers themselves. That means the the MFA Zones app does not provide any security on its own, but rather leverages Nextclouds pre-existing
security to lock down access to certain areas in Nextcloud based on the authentication status of the user.

We think that this is a good thing, because it means that we don't have to reinvent the wheel when it comes to security. We can focus on the trust part of the equation. With that said, we do our best to make sure that
_all_ the parts of the equation are working together. To that end we have comisioned a security audit of the app. You can find the results (which were favourable) [here](https://github.com/SUNET/nextcloud-mfazones/blob/main/audit/MFAZones-and-StepUp-Auth-Security-Assessment-Report-Sunet-Drive-2024-04-v1.1.pdf).

We also do a lot of automated, continuous testing of the app, and we try to respond to any issues that are found. If you find any issues, please report them to us.

## Installation
WARNING: This app requires the `Files Access Control` and `Files Automated Tagging` apps to be enabled. You must also enable these apps before mfazones.

### Through appstore
1. Run `occ app:install mfazones`

### Manually
1. Download the MFA Zones app from the [Nextcloud app store](https://apps.nextcloud.com/apps/mfazones)
2. Extract the downloaded archive to your Nextcloud apps directory.
3. Enable the app in the Nextcloud GUI apps settings or use `occ app:enable mfazones`.


## Usage

After installing and enabling the MFA required zone app, you can create MFA zones for your files and folders. Here's how:

1. Navigate to the file or folder you want to enforce an MFA requirement for.
2. Click on the "MFA Zone" tab in the right-hand sidebar.
3. Toggle the switch to enable or disable the MFA required zone feature.

Users who have not completed MFA verification will not be able to access files or folders within an MFA required zone.

## Contributing

Contributions to the MFA Zones app are welcome! If you encounter a bug, have a feature request, or would like to contribute code, please open an issue or pull request on the [GitHub repository](https://github.com/SUNET/nextcloud-mfazones).

## License

The MFA required zone app is licensed under the [GNU Affero General Public License version 3](https://www.gnu.org/licenses/agpl-3.0.html). See the [LICENSE](LICENSE) file for more information.

## Background
The mfazones app was initially developed by [PonderSource](https://pondersource.com) as part of the [Nextcloud MFA Awareness](https://github.com/pondersource/nextcloud-mfa-awareness) project initiated by [Sunet](https://sunet.se). As of 2024 it is fully maintained by Sunet staff.

