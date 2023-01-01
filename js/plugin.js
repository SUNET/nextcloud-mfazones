var mfazoneFileListPlugin = {
    attach: function(fileList) {
      if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
        return;
      }

      fileList.registerTabView(new OCAMfaZone.MfaZoneTabView());
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
