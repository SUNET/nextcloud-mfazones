// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
var mfazoneFileListPlugin = {
    attach: function(fileList) {
      // if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
      //   return;
      // }

      fileList.registerTabView(new OCA.mfazones.MfaZoneTabView());
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
