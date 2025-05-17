window.onload = function () {
    const overlayImage = document.querySelector(".hero-img-overlay");
    overlayImage.classList.add("loaded");

    const mainImage = document.querySelector(".hero-img");
    mainImage.classList.add("loaded");
};

const svgHeart = document.querySelector('.svg-heart');

svgHeart.addEventListener('click', function () {
    if (svgHeart.classList.contains('favorite')) {
        svgHeart.classList.remove('favorite');
    } else {
        svgHeart.classList.add('favorite');
    }
});



document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.slide');
    slides.forEach((slide, index) => {
        // Delay each slide's animation using the index
        slide.style.animationDelay = `${index * 0.5}s`;
        // Trigger the animation by adding a "loaded" class
        slide.classList.add('loaded');
    });

    // Find the last slide
    const lastSlide = slides[slides.length - 1];

    // Add an event listener to the last slide to remove the "hidden" class from the button
    lastSlide.addEventListener('animationend', function () {
        const rightButton = document.querySelector('.slider-button-section');
        rightButton.classList.add('hidden');
    });

});



window.addEventListener('load', function () {
    // Add the "loaded" class to trigger the transition when the page has loaded.
    const productImage = document.querySelector('.produc-main-big-img');
    productImage.classList.add('loaded');
});


// ------------------------------------------------------

function handleImageLoad(entries, observer) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const image = entry.target;
            image.classList.add('loaded');
            observer.unobserve(image); // Stop observing once loaded
        }
    });
}

// Create an Intersection Observer
const imageObserver = new IntersectionObserver(handleImageLoad, {
    root: null, // Use the viewport as the root
    rootMargin: '0px',
    threshold: 0.5 // When 50% of the image is visible
});

// Target all images with the "plant-collection-tab-img" class
const images = document.querySelectorAll('.plant-collection-tab-img');
images.forEach(image => {
    imageObserver.observe(image); // Start observing the image
});







// -----------------------------------






