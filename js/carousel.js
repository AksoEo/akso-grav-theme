//! This file contains code for image carousels.

const CAN_USE_CLIP_PATH = window.CSS && window.CSS.supports
    && (window.CSS.supports('clip-path: inset(0 0 0 0)')
        || window.CSS.supports('-webkit-clip-path: inset(0 0 0 0)'));

// image carousels
const CAROUSEL_TIMEOUT = 8;

const carousels = document.querySelectorAll('figure.carousel');
for (let carousel of carousels) {
    carousel.classList.add('is-interactive');

    // extract pages and clear carousel
    const pages = [];
    for (let page of carousel.querySelectorAll('.carousel-page')) {
        pages.push({
            image: page.children[0],
            link: page.getAttribute('href') || null,
            caption: page.children[1],
            label: page.dataset.label || '',
        });
        page.parentNode.removeChild(page);
    }
    const paginationLabel = carousel.dataset.paginationLabel || '';

    carousel.innerHTML = '';

    const pagesNode = document.createElement('div');
    pagesNode.className = 'carousel-pages';
    carousel.appendChild(pagesNode);

    const pageNodes = [];

    for (let pageData of pages) {
        const page = document.createElement(pageData.link ? 'a' : 'div');
        if (pageData.link) page.href = pageData.link;
        page.className = 'carousel-page';
        page.style.backgroundImage = `url(${pageData.image.src})`;
        if (pageData.caption.textContent.trim()) {
            // has caption contents!
            page.classList.add('page-has-caption');
            page.appendChild(pageData.caption);
        }
        pagesNode.appendChild(page);
        pageNodes.push(page);
    }

    let position = 0;
    const paginationNodes = [];
    const updatePages = () => {
        const prefersReducedMotion = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        for (let i = 0; i < pageNodes.length; i++) {
            const pageNode = pageNodes[i];
            const offset = i - position;

            if (!prefersReducedMotion && CAN_USE_CLIP_PATH) {
                pageNode.style.transform = `translateX(${offset * 10}%)`;
                const clipOffset = Math.min(Math.abs(offset), 1);
                const clipPath = offset < 0
                    ? `inset(0% ${clipOffset * 90}% 0% 0%)`
                    : offset > 0
                        ? `inset(0% 0% 0% ${clipOffset * 90}%)`
                        : 'inset(0% 0% 0% 0%)';
                pageNode.style.webkitClipPath = pageNode.style.clipPath = clipPath;
                pageNode.style.opacity = 1;
            } else {
                pageNode.style.transform = '';
                pageNode.style.webkitClipPath = pageNode.style.clipPath = '';
                pageNode.style.opacity = offset === 0 ? 1 : 0;
            }

            paginationNodes[i].classList.remove('selected');
            pageNode.classList.remove('is-current');
            if (Math.abs(i - position) < 0.5) {
                paginationNodes[i].classList.add('selected');
                pageNode.classList.add('is-current');
            }
        }
    };

    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'pagination-container';
    paginationContainer.setAttribute('aria-label', paginationLabel);
    carousel.appendChild(paginationContainer);

    const arcIndicators = [];
    let carouselTimeout = CAROUSEL_TIMEOUT;

    for (let i = 0; i < pageNodes.length; i++) {
        const paginationNode = document.createElement('button');
        paginationNode.className = 'pagination-item';
        paginationNode.setAttribute('aria-label', pages[i].label);
        paginationContainer.appendChild(paginationNode);

        const arcIndicator = document.createElement('span');
        arcIndicator.className = 'arc-indicator';
        paginationNode.appendChild(arcIndicator);

        arcIndicators.push(arcIndicator);

        const innerIndicator = document.createElement('span');
        innerIndicator.className = 'inner-indicator';
        paginationNode.appendChild(innerIndicator);

        if (i === 0) paginationNode.checked = true;

        const index = i;
        paginationNode.addEventListener('click', () => {
            position = index;
            carouselTimeout = CAROUSEL_TIMEOUT;
            updatePages();
        });
        paginationNodes.push(paginationNode);
    }

    const updateArcIndicators = () => {
        for (let i = 0; i < arcIndicators.length; i++) {
            const indicator = arcIndicators[i];
            let clipPath = '';
            if (i === position) {
                const t = 1 - carouselTimeout / CAROUSEL_TIMEOUT;
                const angle = t * Math.PI * 2;

                const pointAngles = [
                    Math.min(angle, Math.PI / 4),
                    Math.min(angle, Math.PI * 3 / 4),
                    Math.min(angle, Math.PI * 5 / 4),
                    Math.min(angle, Math.PI * 7 / 4),
                    Math.min(angle, Math.PI * 2),
                ];

                let clipPathPoints = [[0, 0], [0, -1]];

                for (let pointAngle of pointAngles) {
                    const angle = pointAngle - Math.PI / 2;
                    const circleX = Math.cos(angle);
                    const circleY = Math.sin(angle);
                    const circleProjectedY = circleY / Math.abs(circleX); // tan
                    const circleProjectedX = circleX / Math.abs(circleY); // cot

                    if (circleX !== 0 && Math.abs(circleProjectedY) <= 1) {
                        clipPathPoints.push([Math.sign(circleX), circleProjectedY]);
                    } else {
                        clipPathPoints.push([circleProjectedX, Math.sign(circleY)]);
                    }
                }

                clipPath = 'polygon(' + clipPathPoints
                    .map(([x, y]) => `${(x * 50 + 50).toFixed(2)}% ${(y * 50 + 50).toFixed(2)}%`)
                    .join(', ') + ')';
            } else {
                clipPath = 'polygon(50% 50%, 50% 0, 50% 0)';
            }

            indicator.style.webkitClipPath = indicator.style.clipPath = clipPath;
        }
    };

    updatePages();
    updateArcIndicators();

    let lastTime = Date.now();
    const update = () => {
        const dt = Math.min(1 / 30, (Date.now() - lastTime) / 1000);
        lastTime = Date.now();

        requestAnimationFrame(update);

        carouselTimeout -= dt;
        if (carouselTimeout <= 0) {
            carouselTimeout = CAROUSEL_TIMEOUT;
            position++;
            if (position >= pageNodes.length) position = 0;
            updatePages();
        }

        updateArcIndicators();
    };
    update();
}
