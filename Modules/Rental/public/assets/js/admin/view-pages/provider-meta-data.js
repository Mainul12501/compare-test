"use strict";
function readURL(input, viewer) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();

        reader.onload = function (e) {
            $('#'+viewer).attr('src', e.target.result);
        }

        reader.readAsDataURL(input.files[0]);
    }
}

$("#customFileEg1").change(function () {
    readURL(this, 'viewer');
});

$("#coverImageUpload").change(function () {
    readURL(this, 'coverImageViewer');
});
