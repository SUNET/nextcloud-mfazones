// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
var mfazoneFileListPlugin = {
    attach: function(fileList) {
      console.log('FILELIST>>>>>>', fileList);
      // if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
      //   return;
      // }
      console.log("Attaching MFA Zone plugin to " + fileList.id);
      console.log(fileList);
      fileList.registerTabView(new OCA.mfazones.MfaZoneTabView());

      fileList.fileActions.registerAction({
        name: 'mfa',
        displayName: 'MFA Zone',
        type: 1,
        mime: 'all',
        permissions: OC.PERMISSION_NONE,
        iconClass: 'icon-category-security',
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
    
          originalSetFiles.bind(fileList)(filesArray);
          console.log("gathering ids");
          let ids = [];
          document.getElementsByTagName('tr').forEach((tr) => {
            if ((typeof tr.getAttribute('data-id') === 'string') && (typeof parseInt(tr.getAttribute('data-id')) === 'number')) {
              ids.push(tr.getAttribute('data-id'));
            }
          });
          console.log('IDS>>>>>>', ids);
          console.log('attributes of second tr element', document.getElementsByTagName('tr')[1].attributes);
          // const statusUrl = OC.generateUrl('/apps/mfazones/getList');
          // $.ajax({
          //   type: 'GET',
          //   url: statusUrl,
          //   dataType: 'json',
          //   data: JSON.stringify(ids),
          //   async: true,
          //   success: function (response) {
          //       if (response.error){
          //         console.log(response.error);
          //         return;
          //     }
          //     console.log('RESPONSE>>>>>>', response);
          //     document.getElementsByTagName('tr').forEach((tr) => {
          //       if ((typeof tr.getAttribute('data-id') === 'string') && (typeof parseInt(tr.getAttribute('data-id')) === 'number')) {
          //         ids.push(tr.getAttribute('data-id'));
          //         const divs = tr.getElementsByClassName('action-mfa');
          //         if (divs.length === 1) {
          //           const icon = divs[0].getElementsByTagName('span')[0];
          //           const text = divs[0].getElementsByTagName('span')[1];
          //           icon.classList.remove('icon-category-security');
          //           text.innerText = tr.getAttribute('data-id');
          //         }
          //       }
          //     });
          //   },
          //   error: function (xhr, textStatus, thrownError) {
          //     console.log(xhr.status);
          //     console.log(textStatus);
          //     console.log(thrownError);
          //   },
          // });
        }
      ).bind(fileList)

      fileList._getWebdavProperties = (function() {
        // TODO Figure this out! https://github.com/nextcloud/server/blob/371aa1bc5d1c5a5be55ac8e9e812817a68a0cc23/core/src/files/client.js#L505-L512
        return ([].concat(this.filesClient.getPropfindProperties()))//.concat(['DAV:', 'PonderSource']);
      }).bind(fileList)
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
