
document.addEventListener("DOMContentLoaded", function () {
    console.log('DOMContentLoaded: Scrolling to top');
    window.scrollTo({ top: 0, behavior: 'instant' });
});

window.addEventListener('load', function () {
    console.log('Load: Checking scroll position');
    if (window.scrollY !== 0) {
        console.log('Load: Scrolling to top');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
// welcome.js
document.addEventListener('DOMContentLoaded', function () {
    // Hàm chung để xử lý thanh thông báo
    function handleNotification(notificationSelector, options = {}) {
        const notification = document.querySelector(notificationSelector);
        const pageContent = document.querySelector('.page-content');

        if (notification && pageContent) {
            const notificationHeight = notification.offsetHeight;
            pageContent.style.marginTop = `${notificationHeight}px`;

            if (options.autoHideAfter) {
                setTimeout(() => {
                    notification.classList.add('hide');
                }, options.autoHideAfter);
            }

            if (options.closeButtonSelector) {
                const closeButton = document.querySelector(options.closeButtonSelector);
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        notification.classList.add('hide');
                    });
                }
            }

            notification.addEventListener('animationend', (event) => {
                if (event.animationName === 'slideUp') {
                    pageContent.style.marginTop = '0';
                }
            });
        }
    }

    handleNotification('.notification-bar', { autoHideAfter: 3000 });
    handleNotification('.welcome-notification', { closeButtonSelector: '.notification-close' });

    // Xử lý dropdown menu và tooltip
    const userMenu = document.querySelector('.user-menu');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const username = document.querySelector('.username');

    if (userMenu && dropdownMenu && username) {
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            const isVisible = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isVisible ? 'none' : 'block';
            username.setAttribute('data-tooltip-hidden', 'true');
        });

        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                username.removeAttribute('data-tooltip-hidden');
            }
        });

        dropdownMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Xử lý nút menu responsive
    const menuBtn = document.querySelector('.navbar-menu-btn');
    const nav = document.querySelector('nav');
    if (menuBtn && nav) {
        menuBtn.addEventListener('click', function () {
            menuBtn.classList.toggle('active');
            nav.classList.toggle('active');
        });
    }

    // Xử lý slideshow banner
    const bannerImages = document.querySelectorAll('.banner-img');
    let currentIndex = 0;

    function showNextImage() {
        bannerImages[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % bannerImages.length;
        bannerImages[currentIndex].classList.add('active');
    }

    if (bannerImages.length > 0) {
        bannerImages[currentIndex].classList.add('active');
        setInterval(showNextImage, 3000);
    }

    // Lấy dữ liệu ban đầu từ DOM hoặc fetch (giả định từ data attribute hoặc API)
    const allProductsElement = document.getElementById('all-products-data');
    const allProducts = allProductsElement ? JSON.parse(allProductsElement.dataset.products) : [];
    const initialSearchResultsElement = document.getElementById('search-results-data');
    const initialSearchResults = initialSearchResultsElement ? JSON.parse(initialSearchResultsElement.dataset.results) : [];

    const itemsPerRow = 7;
    const rowsPerLoad = 2;
    const itemsPerPage = itemsPerRow * rowsPerLoad;
    let currentItems = itemsPerPage;
    let currentProducts = initialSearchResults.length ? initialSearchResults : allProducts;

    // DOM elements
    const moviesGrid = document.getElementById('movies-grid');
    const loadMoreBtn = document.getElementById('load-more');
    const searchInput = document.querySelector('.navbar-form-search');
    const suggestionsContainer = document.querySelector('.search-suggestions');
    const searchForm = document.querySelector('.navbar-form');
    const bannerSection = document.querySelector('.banner');
    const offersSection = document.querySelector('.offers');
    const liveSection = document.querySelector('.live');
    const liveLink = document.querySelector('.live-link');
    let debounceTimer;

    // Giả định trạng thái đăng nhập từ DOM
    const isLoggedIn = document.body.dataset.loggedIn === 'true';
    const contentForCustomer = document.body.dataset.contentForCustomer === 'true';

    // Hàm tạo thẻ sản phẩm
    function createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'movie-card';
        card.innerHTML = `
            <div class="card-head">
                <img src="${product.image}" alt="${product.name}" class="card-img">
                ${product.discount ? `<div class="discount-badge">-${product.discount}%</div>` : ''}
                ${isLoggedIn ? `
                    <div class="card-overlay">
                        ${contentForCustomer ? `
                            <div class="cart-cart">
                                <button class="add-to-cart" data-product-name="${product.name}">
                                    <ion-icon name="cart"></ion-icon>
                                </button>
                            </div>
                        ` : `
                            <div class="cart-cart">
                                <ion-icon name="cart"></ion-icon>
                            </div>
                        `}
                        <div class="rating">
                            <ion-icon name="star-outline"></ion-icon>
                            <span>${product.rating}</span>
                        </div>
                        <div class="cart">
                            <ion-icon name="cart-outline"></ion-icon>
                            <span class="price">
                                ${product.original_price ? `
                                    <span class="original-price">$${product.original_price.toFixed(2)}</span>
                                    <span class="discounted-price">$${product.discounted_price.toFixed(2)}</span>
                                ` : `$${product.price.toFixed(2)}`}
                            </span>
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="card-body">
                <h3 class="card-title">${product.name}</h3>
            </div>
        `;
        return card;
    }

    // Hàm cập nhật giao diện
    function updateDisplay(products, startIndex = 0, endIndex = itemsPerPage) {
        moviesGrid.innerHTML = '';
        const displayItems = products.slice(startIndex, endIndex);
        displayItems.forEach(product => {
            const card = createProductCard(product);
            moviesGrid.appendChild(card);
        });
        attachAddToCartEvents();

        // Hiển thị/ẩn nút Load More
        loadMoreBtn.style.display = products.length > itemsPerPage ? 'block' : 'none';
    }

    // Xử lý Load More
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            const nextItems = currentProducts.slice(currentItems, currentItems + itemsPerPage);
            nextItems.forEach(product => {
                const card = createProductCard(product);
                moviesGrid.appendChild(card);
            });
            currentItems += itemsPerPage;
            attachAddToCartEvents();

            if (currentItems >= currentProducts.length) {
                loadMoreBtn.style.display = 'none';
            }
        });
    }

    // Khởi tạo giao diện ban đầu
    if (moviesGrid && currentProducts.length > 0) {
        updateDisplay(currentProducts);
    }

    // Xử lý thêm vào giỏ hàng
    function attachAddToCartEvents() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const productName = this.getAttribute('data-product-name');
                fetch(`?add_to_cart=${encodeURIComponent(productName)}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification(`Game ${data.product_name} đã được thêm vào Giỏ hàng`);
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            } else if (data.cart_count > 0) {
                                const cartIcon = document.querySelector('.cart-icon');
                                if (cartIcon) cartIcon.insertAdjacentHTML('beforeend', `<span class="cart-count">${data.cart_count}</span>`);
                            }
                        } else {
                            showNotification(data.error || `Game ${data.product_name} đã có trong giỏ hàng!`, false);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification(`Có lỗi xảy ra: ${error.message}`, false);
                    });
            });
        });
    }

    // Xử lý tìm kiếm với gợi ý
    function updateSearchResults(results) {
        currentProducts = results;
        currentItems = itemsPerPage;
        updateDisplay(currentProducts);
        bannerSection.style.display = 'none';
        offersSection.style.display = 'none';
        liveSection.style.display = 'none';
        liveLink.style.display = 'none';
    }

    if (searchInput && suggestionsContainer && searchForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query === '') {
                bannerSection.style.display = 'block';
                offersSection.style.display = 'block';
                liveSection.style.display = 'block';
                liveLink.style.display = 'block';
                currentProducts = allProducts;
                currentItems = itemsPerPage;
                updateDisplay(currentProducts);
                suggestionsContainer.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`?search=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(results => {
                        suggestionsContainer.innerHTML = '';
                        updateSearchResults(results);
                        if (results.length > 0) {
                            results.forEach(product => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.className = 'suggestion-item';
                                suggestionItem.innerHTML = `
                                <img src="${product.image}" alt="${product.name}">
                                <span>${product.name} - $${product.price}</span>
                            `;
                                suggestionItem.addEventListener('click', () => {
                                    searchInput.value = product.name;
                                    suggestionsContainer.style.display = 'none';
                                    updateSearchResults([product]);
                                });
                                suggestionsContainer.appendChild(suggestionItem);
                            });
                            suggestionsContainer.style.display = 'block';
                        } else {
                            suggestionsContainer.innerHTML = '<div class="suggestion-item">No results found</div>';
                            suggestionsContainer.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                        suggestionsContainer.innerHTML = '<div class="suggestion-item">Error loading suggestions</div>';
                        suggestionsContainer.style.display = 'block';
                    });
            }, 300);
        });

        searchInput.addEventListener('click', function () {
            const query = this.value.trim();
            if (query) {
                fetch(`?search=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(results => {
                        suggestionsContainer.innerHTML = '';
                        if (results.length > 0) {
                            results.forEach(product => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.className = 'suggestion-item';
                                suggestionItem.innerHTML = `
                                <img src="${product.image}" alt="${product.name}">
                                <span>${product.name} - $${product.price}</span>
                            `;
                                suggestionItem.addEventListener('click', () => {
                                    searchInput.value = product.name;
                                    suggestionsContainer.style.display = 'none';
                                    updateSearchResults([product]);
                                });
                                suggestionsContainer.appendChild(suggestionItem);
                            });
                            suggestionsContainer.style.display = 'block';
                        } else {
                            suggestionsContainer.innerHTML = '<div class="suggestion-item">No results found</div>';
                            suggestionsContainer.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        suggestionsContainer.innerHTML = '<div class="suggestion-item">Error loading suggestions</div>';
                        suggestionsContainer.style.display = 'block';
                    });
            }
        });

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                fetch(`?search=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(results => {
                        updateSearchResults(results);
                        suggestionsContainer.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        moviesGrid.innerHTML = '<div class="no-results">Error loading results</div>';
                    });
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }

    // Hàm hiển thị thông báo
    function showNotification(message, isSuccess = true) {
        let notification = document.querySelector('.cart-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'cart-notification';
            document.body.appendChild(notification);
        }
        const iconClass = isSuccess ? '' : 'warning';
        notification.innerHTML = `<div class="checkmark ${iconClass}">
            <ion-icon name="${isSuccess ? 'checkmark-outline' : 'warning-outline'}"></ion-icon>
        </div>
        <div class="message">${message}</div>`;
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 2500);
    }
});

document.addEventListener('DOMContentLoaded', function () {

    // Xử lý slideshow banner
    const bannerImages = document.querySelectorAll('.banner-img');
    const dots = document.querySelectorAll('.dot');
    let currentIndex = 0;
    let slideInterval;

    function updateSlide(index) {
        bannerImages[currentIndex].classList.remove('active');
        dots[currentIndex].classList.remove('active');
        currentIndex = index;
        bannerImages[currentIndex].classList.add('active');
        dots[currentIndex].classList.add('active');
    }

    function startSlideShow() {
        slideInterval = setInterval(() => {
            updateSlide((currentIndex + 1) % bannerImages.length);
        }, 3000);
    }

    if (bannerImages.length > 0 && dots.length > 0) {
        updateSlide(0); // Hiển thị slide đầu tiên
        startSlideShow(); // Bắt đầu tự động chuyển

        // Xử lý click vào chấm
        dots.forEach(dot => {
            dot.addEventListener('click', function () {
                clearInterval(slideInterval); // Dừng tự động chuyển
                const index = parseInt(this.getAttribute('data-index'));
                updateSlide(index);
                startSlideShow(); // Khởi động lại tự động chuyển sau khi click
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const liveGrid = document.querySelector('.live-grid');
    const leftBtn = document.querySelector('.scroll-btn.left');
    const rightBtn = document.querySelector('.scroll-btn.right');
    const scrollAmount = 500; // Cuộn 500px mỗi lần (khoảng 2 card)

    function updateButtons() {
        const scrollLeft = liveGrid.scrollLeft;
        const maxScroll = liveGrid.scrollWidth - liveGrid.clientWidth;

        if (scrollLeft <= 0) {
            leftBtn.classList.add('hidden');
        } else {
            leftBtn.classList.remove('hidden');
        }

        if (scrollLeft >= maxScroll - 1) {
            rightBtn.classList.add('hidden');
        } else {
            rightBtn.classList.remove('hidden');
        }
    }

    leftBtn.addEventListener('click', function () {
        liveGrid.scrollLeft -= scrollAmount;
        setTimeout(updateButtons, 300);
    });

    rightBtn.addEventListener('click', function () {
        liveGrid.scrollLeft += scrollAmount;
        setTimeout(updateButtons, 300);
    });

    updateButtons();
    liveGrid.addEventListener('scroll', updateButtons);
});
