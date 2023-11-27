// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
var mfazoneFileListPlugin = {
    attach: function(fileList) {
      // if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
      //   return;
      // }

      fileList.registerTabView(new OCA.mfazones.MfaZoneTabView());

      const originalSetFiles = fileList.setFiles;

      fileList.setFiles = (
        function(filesArray) {
          // You can find the setFiles function here: https://github.com/nextcloud/server/blob/master/apps/files/js/filelist.js
          console.log('FILES ARRAY 2>>>>>>', filesArray)
          originalSetFiles.bind(fileList)(filesArray)
        }
      ).bind(fileList)

      fileList._getWebdavProperties = (function() {
        // TODO Figure this out! https://github.com/nextcloud/server/blob/371aa1bc5d1c5a5be55ac8e9e812817a68a0cc23/core/src/files/client.js#L505-L512
        return ([].concat(this.filesClient.getPropfindProperties())).concat(['DAV:', 'PonderSource']);
      }).bind(fileList)
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
