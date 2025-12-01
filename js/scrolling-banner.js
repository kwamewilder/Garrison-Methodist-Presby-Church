/**
 * Scrolling Banner JavaScript
 * Fetches and displays scrolling banners dynamically
 */

class ScrollingBanner {
    constructor(containerId, page) {
        this.container = document.getElementById(containerId);
        this.page = page;
        this.banners = [];
        this.init();
    }

    async init() {
        console.log('Initializing banner for page:', this.page);
        await this.fetchBanners();
        if (this.banners.length > 0) {
            console.log('Rendering', this.banners.length, 'banner(s)');
            this.render();
            this.addEventListeners();
            // Add class to body to adjust header position
            document.body.classList.add('has-banner');
        } else {
            console.log('No banners found, hiding container');
            this.container.classList.add('no-banners');
            // Remove class from body since there's no banner
            document.body.classList.remove('has-banner');
            // Adjust header to top
            const header = document.querySelector('.header');
            if (header) {
                header.style.top = '0';
            }
        }
    }

    async fetchBanners() {
        try {
            // Determine the correct API path based on current location
            const isInSubfolder = window.location.pathname.includes('/communities/');
            const apiPath = isInSubfolder ? '../api/get_banners.php' : 'api/get_banners.php';

            console.log('Fetching banners from:', apiPath, 'for page:', this.page);

            const response = await fetch(`${apiPath}?page=${this.page}`);

            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                return;
            }

            const data = await response.json();
            console.log('Banner data received:', data);

            if (data.success && data.banners) {
                this.banners = data.banners;
                console.log('Loaded', this.banners.length, 'banner(s)');
            } else {
                console.warn('No banners or API error:', data);
            }
        } catch (error) {
            console.error('Failed to fetch banners:', error);
        }
    }

    render() {
        console.log('Render called with', this.banners.length, 'banner(s)');
        if (!this.container || this.banners.length === 0) {
            console.warn('Cannot render: container missing or no banners');
            return;
        }

        // Get colors from first banner (or use defaults)
        const firstBanner = this.banners[0];
        const bgColor = firstBanner.background_color || '#5DADEC';
        const textColor = firstBanner.text_color || '#ffffff';

        console.log('Setting banner colors:', { bgColor, textColor });

        // Set container colors
        this.container.style.background = bgColor;
        this.container.style.color = textColor;
        this.container.classList.remove('loading');

        console.log('Banner container classes:', this.container.className);

        // Create banner content (duplicate for seamless loop)
        let bannerHTML = '';
        const bannersToDisplay = [...this.banners, ...this.banners]; // Duplicate for seamless loop

        bannersToDisplay.forEach((banner, index) => {
            const isClickable = banner.link_url && banner.link_url.trim() !== '';
            const tag = isClickable ? 'a' : 'span';
            const href = isClickable ? `href="${banner.link_url}"` : '';
            const target = isClickable ? 'target="_blank" rel="noopener"' : '';
            const clickableClass = isClickable ? 'banner-item-clickable' : '';

            bannerHTML += `
                <${tag} class="banner-item ${clickableClass}" ${href} ${target}>
                    ${this.escapeHtml(banner.banner_text)}
                </${tag}>
                ${index < bannersToDisplay.length - 1 ? '<span class="banner-separator">•</span>' : ''}
            `;
        });

        // Create scrolling div
        const scrollingDiv = document.createElement('div');
        scrollingDiv.className = 'scrolling-banner';
        scrollingDiv.innerHTML = bannerHTML;

        this.container.innerHTML = '';
        this.container.appendChild(scrollingDiv);
    }

    addEventListeners() {
        const scrollingDiv = this.container.querySelector('.scrolling-banner');

        if (scrollingDiv) {
            // Pause on hover
            this.container.addEventListener('mouseenter', () => {
                scrollingDiv.classList.add('paused');
            });

            this.container.addEventListener('mouseleave', () => {
                scrollingDiv.classList.remove('paused');
            });

            // Restart animation when it ends
            scrollingDiv.addEventListener('animationiteration', () => {
                // Animation will loop automatically
            });
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize banner when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const bannerContainer = document.getElementById('scrolling-banner');

    if (bannerContainer) {
        // Determine which page we're on
        const page = bannerContainer.dataset.page || 'homepage';
        new ScrollingBanner('scrolling-banner', page);
    }
});
