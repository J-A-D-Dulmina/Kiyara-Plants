function toggleEdit(fieldId) {
    const valueField = document.getElementById(fieldId + '-value');
    const inputField = document.getElementById(fieldId + '-input');

    valueField.style.display = valueField.style.display === 'none' ? 'inline' : 'none';
    inputField.style.display = inputField.style.display === 'none' ? 'inline' : 'none';
}

document.getElementById('edit-name').addEventListener('click', function () {
    toggleEdit('name');
});

document.getElementById('edit-email').addEventListener('click', function () {
    toggleEdit('email');
});

document.getElementById('edit-age').addEventListener('click', function () {
    toggleEdit('age');
});

document.getElementById('edit-mobile').addEventListener('click', function () {
    toggleEdit('mobile');
});

document.getElementById('edit-address').addEventListener('click', function () {
    toggleEdit('address');
});

document.getElementById('edit-postal').addEventListener('click', function () {
    toggleEdit('postal');
});



document.getElementById("logoutForm").addEventListener("submit", function (event) {
    event.preventDefault();


    var confirmLogout = confirm("Are you sure you want to log out?");
    if (confirmLogout) {

        setTimeout(function () {

            document.getElementById("logoutForm").submit();
        }, 200);
    }
});





// JavaScript to display selected image before form submission
document.getElementById('user_image').addEventListener('change', function (event) {
    const selectedImage = document.getElementById('selected-image');
    selectedImage.src = URL.createObjectURL(event.target.files[0]);
});
