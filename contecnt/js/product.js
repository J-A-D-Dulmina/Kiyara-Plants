
const clickableDivs = document.querySelectorAll(".clickeble-prduct-box");

clickableDivs.forEach(function (div) {
    div.addEventListener("click", function () {
        const url = "product.php";
        window.location.href = url;
    });
});

const clickableDivscat = document.querySelectorAll(".clickeble-prduct-catagerys");

clickableDivscat.forEach(function (div) {
    div.addEventListener("click", function () {
        const url = "shop.php";
        window.location.href = url;
    });
});