
function exportForm(postId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', sureforms_export.ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 400) {
            // The server response is the JSON data
            var jsonData = JSON.parse(xhr.responseText);
            // Create a download link for the JSON data
            var downloadLink = document.createElement('a');
            downloadLink.href = 'data:application/json,' + encodeURIComponent(JSON.stringify(jsonData));
            downloadLink.download = 'sureforms-export-form.json';
   
            // Trigger the download
            downloadLink.click();
        } else {
            console.log('Server Error!');
        }
    };
    xhr.onerror = function() {
        console.log('Connection Error!');
    };
    xhr.send(`action=export_form&post_id=${postId}`);
}

function bulkExport(){
    const applyBtn = document.querySelector('#doaction');
    const select = document.querySelector('#bulk-action-selector-top');
    applyBtn.addEventListener('click',(e)=>{
        if( select.value !== 'export'){
            return;
        }
        e.preventDefault();
        const checkboxes = document.querySelectorAll('#the-list input[type=checkbox]');
        const postIds = [];
        checkboxes.forEach((checkbox) =>{
            if (checkbox.checked) {
              postIds.push(checkbox.value);
            }
           });
        if(postIds.length>0){
            exportForm(postIds)
        }
    })
}

document.addEventListener('DOMContentLoaded', bulkExport)