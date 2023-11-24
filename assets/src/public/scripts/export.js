
function exportForm(postId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', sureforms_export.ajaxurl, true);
    console.log({postId})
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 400) {
            // The server response is the JSON data
            var jsonData = JSON.parse(xhr.responseText);
   
            // Create a download link for the JSON data
            var downloadLink = document.createElement('a');
            downloadLink.href = 'data:application/json,' + encodeURIComponent(JSON.stringify(jsonData));
            downloadLink.download = 'form-data.json';
   
            // Trigger the download
            downloadLink.click();
        } else {
            console.log('Server Error!');
        }
    };
    xhr.onerror = function() {
        console.log('Connection Error!');
    };
    xhr.send(JSON.stringify({action: 'export_form', post_id: postId}));
   }