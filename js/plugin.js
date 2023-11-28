// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
var mfazoneFileListPlugin = {
    attach: function(fileList) {
      console.log('FILELIST>>>>>>', fileList);
      // if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
      //   return;
      // }

      fileList.registerTabView(new OCA.mfazones.MfaZoneTabView());

      fileList.fileActions.registerAction({
        name: 'mfa',
        displayName: '',
        type: 1,
        mime: 'all',
        permissions: OC.PERMISSION_NONE,
        // iconClass: 'icon-category-security',
        actionHandler: function(fileName, context) {
          if (confirm('You must enable two factor authentication to use MFAZone app. Do you want to enable 2FA?')) {
            window.location.href = OC.generateUrl('/settings/user/security');
          }
        },
        // fileName: 'asdf',
      });


      const originalSetFiles = fileList.setFiles;
      fileList.setFiles = (
        function(filesArray) {
          // You can find the setFiles function here: https://github.com/nextcloud/server/blob/master/apps/files/js/filelist.js
          console.log('FILES ARRAY 2>>>>>>', filesArray)
          filesArray.forEach((file) => {
            console.log('seeing', file.name);
            if (file.name === 'asdf') {
              console.log('registering');
              }
            });
    
          originalSetFiles.bind(fileList)(filesArray)
        }
      ).bind(fileList)

      fileList._getWebdavProperties = (function() {
        // TODO Figure this out! https://github.com/nextcloud/server/blob/371aa1bc5d1c5a5be55ac8e9e812817a68a0cc23/core/src/files/client.js#L505-L512
        return ([].concat(this.filesClient.getPropfindProperties()))//.concat(['DAV:', 'PonderSource']);
      }).bind(fileList)
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
