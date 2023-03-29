# MFA Zones Nextcloud App

This is a Nextcloud app that enables file owners and administrators to restrict access to files and folders based on whether or not a logged-in user has passed MFA (multi-factor authentication) verification.

## Caveat
This app relies on a [PR that has not yet been merged](https://github.com/nextcloud/server/pull/37195) into the nextcloud/server master branch, see [our research repo](https://github.com/pondersource/nextcloud-mfa-awareness#nextcloud-mfa-awareness) for a full list of dependencies..

## Requirements

- Nextcloud 20 or later, plus the patch from https://github.com/nextcloud/server/pull/37195
- PHP 7.2 or later
- `File Access Control` (Nextcloud app)
- More [optional external dependencies](https://github.com/pondersource/nextcloud-mfa-awareness#nextcloud-mfa-awareness), depending on your deployment (using SAML? using Global Site Selector?)

## Installation

1. Download the MFA Zones app from the [Nextcloud app store](https://apps.nextcloud.com/apps/mfazones) (FIXME: for now you can download it from this GitHub repo).
2. Extract the downloaded archive to your Nextcloud apps directory.
3. Rename the extracted directory to mfazones.
4. Enable the app in the Nextcloud apps settings.

## Usage

After installing and enabling the MFA required zone app, you can create MFA zones for your files and folders. Here's how:

1. Navigate to the file or folder you want to enforce an MFA requirement for.
2. Click on the "MFA Zone" tab in the right-hand sidebar.
3. Toggle the switch to enable or disable the MFA required zone feature.

Users who have not completed MFA verification will not be able to access files or folders within an MFA required zone.

## Contributing

Contributions to the MFA Zones app are welcome! If you encounter a bug, have a feature request, or would like to contribute code, please open an issue or pull request on the [GitHub repository](https://github.com/pondersource/mfazones).

## License

The MFA required zone app is licensed under the [GNU Affero General Public License version 3](https://www.gnu.org/licenses/agpl-3.0.html). See the [LICENSE](LICENSE) file for more information.
