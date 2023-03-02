# MFA required zone Nextcloud App

The MFA required zone app is a Nextcloud app that enables file owners and administrators to restrict access to files and folders based on whether or not a user has MFA (multi-factor authentication) verification.

## Requirements

- Nextcloud 20 or later
- PHP 7.2 or later
- `File Access Control` (Nextcloud app)

## Installation

1. Download the MFA required zone app from the [Nextcloud app store](https://apps.nextcloud.com/apps/mfaverifiedzone).
2. Extract the downloaded archive to your Nextcloud apps directory.
3. Rename the extracted directory to mfaverifiedzone.
4. Enable the app in the Nextcloud apps settings.

## Usage

After installing and enabling the MFA required zone app, you can create MFA required zones for your files and folders. Here's how:

1. Navigate to the file or folder you want to add an MFA required zone to.
2. Click on the "MFA Zone" tab in the right-hand sidebar.
3. Toggle the switch to enable or disable the MFA required zone feature.

Users who have not completed MFA verification will not be able to access files or folders within an MFA required zone.

## Contributing

Contributions to the MFA required zone app are welcome! If you encounter a bug, have a feature request, or would like to contribute code, please open an issue or pull request on the [GitHub repository](https://github.com/pondersource/mfaverifiedzone).

## License

The MFA required zone app is licensed under the [GNU Affero General Public License version 3](https://www.gnu.org/licenses/agpl-3.0.html). See the [LICENSE](LICENSE) file for more information.