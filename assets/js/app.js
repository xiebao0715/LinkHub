(function () {
    'use strict';

    const THEME_KEY = 'link_manager_theme';
    const RECENT_KEY = 'link_manager_recent';
    const MAX_RECENT = 8;
    const CAROUSEL_INTERVAL = 5000;

    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const serverTheme = html.dataset.theme || 'light';
    const serverRev = html.dataset.themeRev || '';

    function saveTheme(theme) {
        localStorage.setItem(THEME_KEY, JSON.stringify({ theme, rev: serverRev }));
    }

    function loadTheme() {
        try {
            const stored = JSON.parse(localStorage.getItem(THEME_KEY) || 'null');
            if (stored && stored.rev === serverRev && stored.theme) {
                html.dataset.theme = stored.theme;
                return stored.theme;
            }
        } catch (e) { /* ignore */ }
        html.dataset.theme = serverTheme;
        saveTheme(serverTheme);
        return serverTheme;
    }

    function getTheme() {
        return html.dataset.theme || serverTheme;
    }

    function setTheme(theme) {
        html.dataset.theme = theme;
        saveTheme(theme);
    }

    loadTheme();

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            setTheme(getTheme() === 'light' ? 'dark' : 'light');
        });
    }

    function trackClick(type, id) {
        if (!id) return;
        fetch('/api/track.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, id: parseInt(id, 10) })
        }).catch(() => {});
    }

    // 外部链接离开确认
    const leaveSiteModal = document.getElementById('leaveSiteModal');
    const leaveSiteUrl = document.getElementById('leaveSiteUrl');
    let pendingExternalUrl = '';

    function openLeaveSiteModal(url) {
        if (!leaveSiteModal || !url) return;
        pendingExternalUrl = url;
        if (leaveSiteUrl) leaveSiteUrl.textContent = url;
        leaveSiteModal.classList.add('is-open');
        leaveSiteModal.setAttribute('aria-hidden', 'false');
    }

    function closeLeaveSiteModal() {
        if (!leaveSiteModal) return;
        leaveSiteModal.classList.remove('is-open');
        leaveSiteModal.setAttribute('aria-hidden', 'true');
        pendingExternalUrl = '';
    }

    function confirmLeaveSite() {
        if (pendingExternalUrl) {
            window.open(pendingExternalUrl, '_blank', 'noopener,noreferrer');
        }
        closeLeaveSiteModal();
    }

    document.getElementById('leaveSiteCancel')?.addEventListener('click', closeLeaveSiteModal);
    document.getElementById('leaveSiteConfirm')?.addEventListener('click', confirmLeaveSite);
    document.getElementById('leaveSiteBackdrop')?.addEventListener('click', closeLeaveSiteModal);

    document.addEventListener('click', (e) => {
        const linkEl = e.target.closest('.external-link');
        if (!linkEl) return;
        e.preventDefault();
        const url = linkEl.dataset.linkUrl;
        if (!url) return;
        if (linkEl.dataset.linkId) {
            trackClick('link', linkEl.dataset.linkId);
        }
        openLeaveSiteModal(url);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (leaveSiteModal?.classList.contains('is-open')) {
            closeLeaveSiteModal();
        }
    });

    // Carousel（Coverflow 卡片效果）
    const carousel = document.getElementById('carousel');
    if (carousel) {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const dots = carousel.querySelectorAll('.dot');
        const prevBtn = document.getElementById('carouselPrev');
        const nextBtn = document.getElementById('carouselNext');
        let current = 0;
        let timer = null;
        let isAnimating = false;
        let touchStartX = 0;
        let touchDeltaX = 0;
        const ANIM_MS = 450;

        function slideOffset(index, center, total) {
            let diff = index - center;
            const half = total / 2;
            if (diff > half) diff -= total;
            if (diff < -half) diff += total;
            return diff;
        }

        function updateView() {
            slides.forEach((slide, i) => {
                const offset = slideOffset(i, current, slides.length);
                slide.classList.remove('active', 'prev', 'next', 'far');
                if (offset === 0) slide.classList.add('active');
                else if (offset === -1) slide.classList.add('prev');
                else if (offset === 1) slide.classList.add('next');
                else slide.classList.add('far');
            });
            dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
            updateCarouselLayout();
        }

        const viewport = carousel.querySelector('.carousel-viewport');
        const MOBILE_CAROUSEL = 640;

        function getMaxSlideWidthRatio() {
            if (slides.length === 1) return 1;
            return window.innerWidth <= MOBILE_CAROUSEL ? 0.88 : 0.68;
        }

        function fitCarouselFrame(vw, maxWidthRatio, imgW, imgH, minH, maxH) {
            const imgRatio = imgH / imgW;
            const maxW = vw * maxWidthRatio;
            let w = maxW;
            let h = w * imgRatio;

            if (h > maxH) {
                h = maxH;
                w = h / imgRatio;
            }
            if (h < minH) {
                h = minH;
                w = h / imgRatio;
                if (w > maxW) {
                    w = maxW;
                    h = w * imgRatio;
                }
            }

            return {
                width: w,
                height: h,
                widthPct: Math.min((w / vw) * 100, 100)
            };
        }

        function updateCarouselLayout() {
            if (!viewport) return;

            const activeSlide = slides[current];
            const img = activeSlide?.querySelector('img');
            if (!img) return;

            const apply = () => {
                if (!img.naturalWidth) return;
                const isMobile = window.innerWidth <= MOBILE_CAROUSEL;
                const maxWidthRatio = getMaxSlideWidthRatio();
                const minH = isMobile ? 160 : 180;
                const maxH = isMobile
                    ? Math.min(window.innerHeight * 0.65, 520)
                    : 480;
                const vw = viewport.clientWidth || carousel.clientWidth;
                const frame = fitCarouselFrame(
                    vw,
                    maxWidthRatio,
                    img.naturalWidth,
                    img.naturalHeight,
                    minH,
                    maxH
                );

                viewport.style.height = `${Math.round(frame.height)}px`;
                carousel.style.setProperty('--carousel-active-w', `${frame.widthPct}%`);
            };

            if (img.complete && img.naturalWidth) apply();
            else img.addEventListener('load', apply, { once: true });
        }

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(updateCarouselLayout, 150);
        });

        slides.forEach(slide => {
            slide.querySelector('img')?.addEventListener('load', updateCarouselLayout, { once: true });
        });

        function goTo(index) {
            if (slides.length <= 1 || isAnimating) return;
            const next = (index + slides.length) % slides.length;
            if (next === current) return;
            current = next;
            isAnimating = true;
            updateView();
            window.setTimeout(() => { isAnimating = false; }, ANIM_MS);
        }

        function startAutoPlay() {
            clearInterval(timer);
            if (slides.length <= 1) return;
            timer = setInterval(() => goTo(current + 1), CAROUSEL_INTERVAL);
        }

        function pauseAutoPlay() {
            clearInterval(timer);
        }

        prevBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            goTo(current - 1);
            startAutoPlay();
        });

        nextBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            goTo(current + 1);
            startAutoPlay();
        });

        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                goTo(i);
                startAutoPlay();
            });
        });

        carousel.addEventListener('mouseenter', pauseAutoPlay);
        carousel.addEventListener('mouseleave', startAutoPlay);

        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            touchDeltaX = 0;
            pauseAutoPlay();
        }, { passive: true });

        carousel.addEventListener('touchmove', (e) => {
            touchDeltaX = e.touches[0].clientX - touchStartX;
        }, { passive: true });

        carousel.addEventListener('touchend', () => {
            if (Math.abs(touchDeltaX) > 50) {
                goTo(touchDeltaX > 0 ? current - 1 : current + 1);
            }
            startAutoPlay();
        });

        carousel.querySelectorAll('[data-carousel-id]').forEach(link => {
            link.addEventListener('click', () => {
                trackClick('carousel', link.dataset.carouselId);
            });
        });

        updateView();
        startAutoPlay();
    }

    function getRecent() {
        try {
            return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        } catch {
            return [];
        }
    }

    function saveRecent(keyword) {
        let recent = getRecent().filter(k => k !== keyword);
        recent.unshift(keyword);
        recent = recent.slice(0, MAX_RECENT);
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
        renderRecent();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderRecent() {
        const container = document.querySelector('#recentTags .tags-list');
        if (!container) return;
        const recent = getRecent();
        if (recent.length === 0) {
            container.innerHTML = '<span class="tags-empty">暂无记录</span>';
            return;
        }
        container.innerHTML = recent.map(k =>
            `<button class="tag" data-keyword="${escapeHtml(k)}">${escapeHtml(k)}</button>`
        ).join('');
        container.querySelectorAll('.tag').forEach(tag => {
            tag.addEventListener('click', () => {
                searchInput.value = tag.dataset.keyword;
                updateSearchClear();
                doSearch();
            });
        });
    }

    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    const searchBtn = document.getElementById('searchBtn');
    const searchScope = document.getElementById('searchScope');
    const scopePicker = document.getElementById('scopePicker');
    const scopePickerTrigger = document.getElementById('scopePickerTrigger');
    const scopePickerMenu = document.getElementById('scopePickerMenu');
    const scopePickerIcon = document.getElementById('scopePickerIcon');
    const scopePickerLabel = document.getElementById('scopePickerLabel');
    const resultsSection = document.getElementById('resultsSection');
    const resultsGrid = document.getElementById('resultsGrid');
    const resultsCount = document.getElementById('resultsCount');
    const hotLinksSection = document.getElementById('hotLinksSection');

    function updateSearchClear() {
        if (!searchClear || !searchInput) return;
        const show = searchInput.value.trim().length > 0;
        searchClear.hidden = !show;
        searchClear.classList.toggle('is-visible', show);
    }

    function clearSearchInput() {
        if (!searchInput) return;
        searchInput.value = '';
        updateSearchClear();
        resultsSection.style.display = 'none';
        if (hotLinksSection) hotLinksSection.style.display = '';
        searchInput.focus();
    }

    renderRecent();
    updateSearchClear();

    function closeScopePicker() {
        if (!scopePicker) return;
        scopePicker.classList.remove('open');
        scopePickerTrigger?.setAttribute('aria-expanded', 'false');
    }

    function selectScopeOption(option) {
        if (!option || !searchScope) return;
        scopePickerMenu?.querySelectorAll('.scope-picker-option').forEach(el => el.classList.remove('active'));
        option.classList.add('active');
        searchScope.value = option.dataset.value;
        if (scopePickerIcon) {
            scopePickerIcon.innerHTML = option.querySelector('.scope-picker-icon')?.innerHTML || '';
        }
        if (scopePickerLabel) {
            scopePickerLabel.textContent = option.querySelector('.scope-picker-text')?.textContent || '';
        }
        closeScopePicker();
    }

    scopePickerTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = scopePicker.classList.toggle('open');
        scopePickerTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    scopePickerMenu?.querySelectorAll('.scope-picker-option').forEach(option => {
        option.addEventListener('click', () => selectScopeOption(option));
    });

    document.addEventListener('click', (e) => {
        if (scopePicker && !scopePicker.contains(e.target)) {
            closeScopePicker();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeScopePicker();
    });

    async function doSearch() {
        const q = searchInput.value.trim();
        if (!q) {
            resultsSection.style.display = 'none';
            if (hotLinksSection) hotLinksSection.style.display = '';
            return;
        }

        saveRecent(q);

        const scope = searchScope.value;
        resultsSection.style.display = '';
        if (hotLinksSection) hotLinksSection.style.display = 'none';
        resultsGrid.innerHTML = '<div class="loading-hint"><i class="fa-solid fa-spinner fa-spin"></i> 搜索中...</div>';

        try {
            const res = await fetch(`/api/search.php?q=${encodeURIComponent(q)}&scope=${encodeURIComponent(scope)}`);
            const data = await res.json();

            resultsCount.textContent = `共 ${data.total} 条结果`;

            if (data.data.length === 0) {
                resultsGrid.innerHTML = '<p class="empty-hint"><i class="fa-solid fa-inbox"></i> 没有找到相关资源</p>';
                return;
            }

            resultsGrid.innerHTML = data.data.map(item => `
                <a href="#" class="result-card external-link" data-link-id="${item.id}" data-link-url="${escapeHtml(item.url)}">
                    <div class="result-icon">
                        ${item.platform_logo
                            ? `<img src="/uploads/${escapeHtml(item.platform_logo)}" alt="">`
                            : escapeHtml(item.platform_name.charAt(0))}
                    </div>
                    <div class="result-info">
                        <div class="result-title">${escapeHtml(item.title)}</div>
                        <div class="result-meta">${escapeHtml(item.url)}</div>
                    </div>
                    <span class="result-platform">${escapeHtml(item.platform_name)}</span>
                    <span class="result-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                </a>
            `).join('');
        } catch {
            resultsGrid.innerHTML = '<p class="empty-hint">搜索失败，请稍后重试</p>';
        }
    }

    searchBtn?.addEventListener('click', doSearch);
    searchInput?.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });
    searchInput?.addEventListener('input', updateSearchClear);
    searchClear?.addEventListener('click', clearSearchInput);

    document.querySelectorAll('.hot-tag, .reco-tag').forEach(tag => {
        tag.addEventListener('click', () => {
            searchInput.value = tag.dataset.keyword;
            updateSearchClear();
            doSearch();
        });
    });

    // 公告弹窗
    const announcementModal = document.getElementById('announcementModal');
    if (announcementModal) {
        const ANNOUNCE_KEY = 'link_manager_announce_closed';
        const backdrop = document.getElementById('announcementBackdrop');
        const closeBtn = document.getElementById('announcementClose');
        const confirmBtn = document.getElementById('announcementConfirm');

        function openAnnouncement() {
            announcementModal.classList.add('is-open');
            announcementModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeAnnouncement() {
            announcementModal.classList.remove('is-open');
            announcementModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            sessionStorage.setItem(ANNOUNCE_KEY, '1');
        }

        if (!sessionStorage.getItem(ANNOUNCE_KEY)) {
            requestAnimationFrame(() => openAnnouncement());
        }

        backdrop?.addEventListener('click', closeAnnouncement);
        closeBtn?.addEventListener('click', closeAnnouncement);
        confirmBtn?.addEventListener('click', closeAnnouncement);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && announcementModal.classList.contains('is-open')) {
                closeAnnouncement();
            }
        });
    }

    if (new URLSearchParams(location.search).has('page')) {
        const section = document.getElementById('hotLinksSection');
        if (section) {
            requestAnimationFrame(() => section.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
    }
})();
