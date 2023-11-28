
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
let data;
const handleFileChange = (event) => {
    const file = event.target.files[0];
    const reader = new FileReader();
    reader.onload = (event) => {
        const currData = JSON.parse(event.target.result);
        data = currData;
        const impSubmitBtn = document.querySelector('#import-form-submit');
        if(impSubmitBtn){
            impSubmitBtn.removeAttribute('disabled')
        }
    };
    reader.readAsText(file);
};
const handleImportForm = () =>{
    if( ! data ){
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', sureforms_export.ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    //const site_url = sureforms_admin.site_url;
    // fetch( `${ site_url }/wp-json/sureforms/v1/sureforms_import`, {
    //     method: 'POST',
    //     body: JSON.stringify(data),
    // } )
    // .then( ( response ) => {
    //     if ( response.ok ) {
    //         window.location.reload();
    //         return response;
    //     }
    // } )
    // .catch( ( e ) => {
    //     console.log( e );
    // } );
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                window.location.reload();
            } else {
                console.log('Error:', xhr.status, xhr.statusText);
            }
        }
    };

    xhr.onerror = function (error) {
        console.log('Request failed:', error);
    };

    // xhr.send(JSON.stringify(data));
    xhr.send(`action=import_form&data=${JSON.stringify(data)}`);
}

function importForm(){
    const importBtn = document.querySelector('.srfm-import-btn');
    const importContainer = document.querySelector('.srfm-import-wrap');
    const impSubmitBtn = document.querySelector('#import-form-submit');
    if(importBtn){
        importBtn.addEventListener('click',()=>{
            if(importContainer){
                importContainer.style.display = 'block';
            }
        })
    }
    impSubmitBtn.addEventListener('click',(e)=>{
        e.preventDefault();
        handleImportForm();
    })
}
document.addEventListener('DOMContentLoaded',importForm)