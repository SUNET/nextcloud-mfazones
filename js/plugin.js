// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
var mfazoneFileListPlugin = {
    attach: function(fileList) {
      // console.log('FILELIST>>>>>>', fileList);
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
          const statusUrl = OC.generateUrl('/apps/mfazones/getMfaStatus');
          $.ajax({
            type: 'GET',
            url: statusUrl,
            dataType: 'json',
            async: true,
            success: function (response) {
              if (response.error){
                  console.log(response.error);
                  return;
              }
              if (response.mfa_passed !== true) {
                const choice = confirm('This folder requires Multi Factor Authentication. Do you want to enable it for your account?')
                if (choice) {
                  window.location.href = OC.generateUrl('/settings/user/security');
                }
              } else {
                alert('You have already enabled Multi Factor Authentication for your account.');
              }
            }
          });
        },
        // fileName: 'asdf',
      });

      const originalSetFiles = fileList.setFiles;
      fileList.setFiles = (
        function(filesArray) {
          // You can find the setFiles function here: https://github.com/nextcloud/server/blob/master/apps/files/js/filelist.js
          // console.log('FILES ARRAY 2>>>>>>', filesArray)
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
          // console.log('IDS>>>>>>', ids);
          // const trs = document.getElementsByTagName('tr');
          // for (let i=1; i<trs.length; i++) {
          //   console.log('data-requires-mfa attribute of', trs[i].getAttribute('data-file'), trs[i].getAttribute('data-requires-mfa'));
          //   if (trs[i].getAttribute('data-requires-mfa') === 'true') {
          //     console.log('adding icon to', trs[i]);
          //     const divs = trs[i].getElementsByClassName('action-mfa');
          //     if (divs.length === 1) {
          //       const icon = divs[0].getElementsByTagName('span')[0];
          //       const text = divs[0].getElementsByTagName('span')[1];
          //       icon.classList.add('icon-category-security');
          //       text.innerText = 'MFA Zone';
          //     }
          //   }
          // }
          
          // const statusUrl = OC.generateUrl('/apps/mfazones/getList');
        }
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
          //         ids.push(tr.getAttribute('data-id'));2
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
      ).bind(fileList)

      fileList._getWebdavProperties = (function() {
        // TODO Figure this out! https://github.com/nextcloud/server/blob/371aa1bc5d1c5a5be55ac8e9e812817a68a0cc23/core/src/files/client.js#L505-L512
        return ([].concat(this.filesClient.getPropfindProperties()))//.concat(['DAV:', 'PonderSource']);
      }).bind(fileList)
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);
