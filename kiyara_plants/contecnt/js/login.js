const overlay = document.getElementById('overlay');
const loginMainSection = document.getElementById('login-main-section');

overlay.addEventListener('click', () => {
    loginMainSection.style.display = 'none'; // Hide the login section
    window.location.href = 'index.html'; // Redirect to the home page
});
