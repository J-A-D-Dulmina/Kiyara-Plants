// document.addEventListener('DOMContentLoaded', function () {
//     const productRow = document.querySelector('.product-row');
//     const prevButton = document.getElementById('slider-left');
//     const nextButton = document.getElementById('slider-right');

//     function moveProduct(direction) {
//         const firstProduct = productRow.firstElementChild;
//         const lastProduct = productRow.lastElementChild;

//         if (direction === 'next') {
//             productRow.removeChild(firstProduct);
//             productRow.appendChild(firstProduct);
//         } else if (direction === 'prev') {
//             productRow.removeChild(lastProduct);
//             productRow.insertBefore(lastProduct, productRow.firstElementChild);
//         }
//     }

//     prevButton.addEventListener('click', function () {
//         moveProduct('prev');
//     });

//     nextButton.addEventListener('click', function () {
//         moveProduct('next');
//     });
// });


document.addEventListener('DOMContentLoaded', function () {
    // Product slider functionality for homepage
    const productRow = document.querySelector('.product-row');
    const prevButton = document.getElementById('slider-left');
    const nextButton = document.getElementById('slider-right');

    if (productRow && prevButton && nextButton) {
        function moveProduct(direction) {
            const firstProduct = productRow.firstElementChild;

            if (direction === 'next') {
                firstProduct.style.transform = 'scale(0.8)';
                productRow.removeChild(firstProduct);
                productRow.appendChild(firstProduct);
                setTimeout(() => {
                    firstProduct.style.transform = 'scale(1)';
                }, 1);
            } else if (direction === 'prev') {
                // Do not apply scaling effect when moving to the previous slide
                productRow.removeChild(firstProduct);
                productRow.appendChild(firstProduct);
            }
        }

        prevButton.addEventListener('click', function () {
            moveProduct('prev');
        });

        nextButton.addEventListener('click', function () {
            moveProduct('next');
        });
    }

    // Product hover and click functionality for shop page
    const productSlides = document.querySelectorAll('.product-slide');
    const productSlideBoxes = document.querySelectorAll('.product-slide-box');
    const addCartButtons = document.querySelectorAll('.prodcut-clik-main');

    // Function to close the buttons and remove the blur effect
    const closeButtonsAndBlur = (clickedIndex) => {
        productSlides.forEach((slide, index) => {
            if (index !== clickedIndex) {
                slide.classList.remove('blur');
                if (addCartButtons[index]) {
                    addCartButtons[index].classList.remove('visible');
                }
            }
        });
    };

    // Function to toggle the hover class for a specific slide
    const toggleHoverEffect = (slide, shouldEnable) => {
        if (shouldEnable) {
            slide.classList.remove('product-slide-no-hover');
        } else {
            slide.classList.add('product-slide-no-hover');
        }
    };

    // Add click event listeners to each product-slide-box
    productSlideBoxes.forEach((box, index) => {
        const slide = box.querySelector('.product-slide');
        const buttons = box.querySelector('.prodcut-clik-main');
        
        if (slide && buttons) {
            box.addEventListener('click', (event) => {
                slide.classList.toggle('blur');
                buttons.classList.toggle('visible');
                toggleHoverEffect(slide, !slide.classList.contains('blur'));
                closeButtonsAndBlur(index);
                event.stopPropagation();
            });
            
            // Prevent click on buttons from toggling the container
            buttons.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

    // Add a click event listener to the document to close all product interactions
    document.addEventListener('click', () => {
        closeButtonsAndBlur(-1); // Pass -1 to close all product slides
        
        productSlides.forEach((slide) => {
            toggleHoverEffect(slide, true);
        });
    });
});






const productSlides = document.querySelectorAll('.product-slide');
const addCartButtons = document.querySelectorAll('.prodcut-clik-main');

// Function to close the buttons and remove the blur effect
const closeButtonsAndBlur = (clickedIndex) => {
    productSlides.forEach((slide, index) => {
        if (index !== clickedIndex) {
            slide.classList.remove('blur');
            addCartButtons[index].classList.remove('visible');
        }
    });
};

// Function to toggle the hover class for a specific slide
const toggleHoverEffect = (slide, shouldEnable) => {
    if (shouldEnable) {
        slide.classList.remove('product-slide-no-hover');
    } else {
        slide.classList.add('product-slide-no-hover');
    }
};

// Add click event listeners to each product-slide
productSlides.forEach((slide, index) => {
    slide.addEventListener('click', (event) => {
        slide.classList.toggle('blur');
        addCartButtons[index].classList.toggle('visible');
        toggleHoverEffect(slide, !slide.classList.contains('blur'));
        closeButtonsAndBlur(index);
        event.stopPropagation();
    });
});

// Add a click event listener to the document
document.addEventListener('click', () => {
    closeButtonsAndBlur(-1); // Pass -1 to close all product slides
});



















