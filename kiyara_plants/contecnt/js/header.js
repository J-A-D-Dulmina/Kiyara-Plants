const searchIcon = document.getElementById('search-icon');
const searchContainer = document.getElementById('search-container');
const searchInput = document.getElementById('search-input');

searchIcon.addEventListener('click', function () {
    searchContainer.classList.toggle('active');
    if (searchContainer.classList.contains('active')) {
        searchInput.style.width = '100%';
        searchInput.style.display = 'block';
        searchInput.focus();
    } else {
        searchInput.style.width = '0';
        searchInput.style.display = 'none';
    }
});

// Handle search form submission
searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const searchQuery = searchInput.value.trim();
        if (searchQuery) {
            window.location.href = 'shop.php?search=' + encodeURIComponent(searchQuery);
        }
    }
});

const header = document.querySelector(".header");
const viewportHeight = window.innerHeight; // Get the viewport height

window.onscroll = () => {
    const scrollPosition = window.scrollY;

    if (scrollPosition >= viewportHeight * 0.75) {
        header.classList.add("sticky"); // Add the .sticky class to apply the blur effect
    } else {
        header.classList.remove("sticky"); // Remove the .sticky class to remove the blur effect
    }
};



