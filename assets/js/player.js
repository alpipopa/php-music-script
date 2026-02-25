/**
 * Musican - مشغل الصوتيات الاحترافي العائم
 * Floating Audio Player - Vanilla JS
 */

const MusicanPlayer = (function () {
    'use strict';

    // ==================== الحالة ====================
    let state = {
        playlist: [],
        currentIndex: -1,
        isPlaying: false,
        volume: 0.8,
        isMuted: false,
        isShuffled: false,
        repeatMode: 0, // 0=لا، 1=كل، 2=واحد
        playlistOpen: false,
    };

    // ==================== عناصر DOM ====================
    let audio = null;
    let els = {};

    // ==================== التهيئة ====================
    function init() {
        audio = new Audio();
        audio.preload = 'auto';
        audio.volume = state.volume;

        els = {
            player: document.getElementById('musican-player'),
            playlist: document.getElementById('musican-playlist'),
            coverImg: document.getElementById('player-cover'),
            title: document.getElementById('player-title'),
            performer: document.getElementById('player-performer'),
            playPause: document.getElementById('btn-play-pause'),
            prev: document.getElementById('btn-prev'),
            next: document.getElementById('btn-next'),
            shuffle: document.getElementById('btn-shuffle'),
            repeat: document.getElementById('btn-repeat'),
            progress: document.getElementById('progress-bar'),
            progressFill: document.getElementById('progress-fill'),
            currentTime: document.getElementById('current-time'),
            totalTime: document.getElementById('total-time'),
            volumeIcon: document.getElementById('btn-volume'),
            volumeSlider: document.getElementById('volume-slider'),
            togglePlaylist: document.getElementById('btn-toggle-playlist'),
            playlistItems: document.getElementById('playlist-items'),
            playlistClose: document.getElementById('btn-close-playlist'),
            hidePlayerBtn: document.getElementById('btn-hide-player'),
        };

        // ربط الأحداث
        bindEvents();

        // تحميل القائمة المحفوظة
        loadSavedState();
    }

    function bindEvents() {
        // أزرار التحكم
        els.playPause?.addEventListener('click', togglePlay);
        els.prev?.addEventListener('click', playPrev);
        els.next?.addEventListener('click', playNext);
        els.shuffle?.addEventListener('click', toggleShuffle);
        els.repeat?.addEventListener('click', cycleRepeat);
        els.volumeIcon?.addEventListener('click', toggleMute);
        els.togglePlaylist?.addEventListener('click', togglePlaylist);

        // ربط زر الإغلاق بقوة أكبر
        const closeBtn = els.playlistClose || document.getElementById('btn-close-playlist');
        closeBtn?.addEventListener('click', closePlaylist);

        els.hidePlayerBtn?.addEventListener('click', hidePlayer);

        // شريط التقدم
        els.progress?.addEventListener('click', seekTo);
        els.volumeSlider?.addEventListener('input', e => setVolume(e.target.value / 100));

        // أحداث الصوت
        audio.addEventListener('timeupdate', onTimeUpdate);
        audio.addEventListener('ended', onEnded);
        audio.addEventListener('loadedmetadata', onLoaded);
        audio.addEventListener('play', () => updatePlayBtn(true));
        audio.addEventListener('pause', () => updatePlayBtn(false));
        audio.addEventListener('error', onError);

        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', onKeyDown);
    }

    // ==================== إضافة للقائمة ====================
    function addToPlaylist(track, playNow = false) {
        // التحقق من وجود المقطع
        const exists = state.playlist.findIndex(t => t.id === track.id);

        if (exists === -1) {
            state.playlist.push(track);
            renderPlaylistItem(track, state.playlist.length - 1);
        }

        if (playNow) {
            const idx = exists === -1 ? state.playlist.length - 1 : exists;
            loadTrack(idx);
            play();
        }

        showPlayer();
        saveState();
    }

    function addMultipleToPlaylist(tracks, playFirst = false) {
        tracks.forEach((track, i) => {
            const exists = state.playlist.findIndex(t => t.id === track.id);
            if (exists === -1) {
                state.playlist.push(track);
                renderPlaylistItem(track, state.playlist.length - 1);
            }
        });
        if (playFirst && tracks.length > 0) {
            const idx = state.playlist.findIndex(t => t.id === tracks[0].id);
            loadTrack(idx);
            play();
        }
        showPlayer();
        saveState();
    }

    function clearPlaylist() {
        state.playlist = [];
        state.currentIndex = -1;
        if (els.playlistItems) els.playlistItems.innerHTML = '';
        audio.pause();
        audio.src = '';
        state.isPlaying = false;
        updatePlayBtn(false);
        resetDisplay();
    }

    // ==================== التشغيل ====================
    function loadTrack(index) {
        if (index < 0 || index >= state.playlist.length) return;

        state.currentIndex = index;
        const track = state.playlist[index];

        audio.src = track.url;
        audio.load();

        // تحديث المعلومات
        if (els.title) els.title.textContent = track.title || 'بدون عنوان';
        if (els.performer) els.performer.textContent = track.performer || '';
        if (els.coverImg) els.coverImg.src = track.cover || (window.MusicanConfig ? window.MusicanConfig.defaultCover : '');

        // تحديث قائمة التشغيل
        updatePlaylistActive(index);

        // إرسال حدث
        document.dispatchEvent(new CustomEvent('musican:trackChange', { detail: track }));

        saveState();
    }

    function play() {
        audio.play().catch(e => console.warn('تعذّر التشغيل:', e));
    }

    function pause() {
        audio.pause();
    }

    function togglePlay() {
        if (!audio.src) {
            if (state.playlist.length > 0) loadTrack(0);
            else return;
        }
        state.isPlaying ? pause() : play();
        state.isPlaying = !audio.paused;
    }

    function playNext() {
        if (state.playlist.length === 0) return;

        let next;
        if (state.isShuffled) {
            next = Math.floor(Math.random() * state.playlist.length);
        } else {
            next = state.currentIndex + 1;
            if (next >= state.playlist.length) {
                if (state.repeatMode === 1) next = 0;
                else return;
            }
        }
        loadTrack(next);
        play();
    }

    function playPrev() {
        if (state.playlist.length === 0) return;
        if (audio.currentTime > 3) {
            audio.currentTime = 0;
            return;
        }
        let prev = state.currentIndex - 1;
        if (prev < 0) prev = state.repeatMode === 1 ? state.playlist.length - 1 : 0;
        loadTrack(prev);
        play();
    }

    function playAtIndex(index) {
        loadTrack(index);
        play();
    }

    // ==================== الصوت ====================
    function setVolume(val) {
        state.volume = Math.max(0, Math.min(1, val));
        audio.volume = state.volume;
        state.isMuted = state.volume === 0;
        updateVolumeUI();
    }

    function toggleMute() {
        state.isMuted = !state.isMuted;
        audio.muted = state.isMuted;
        if (els.volumeSlider) els.volumeSlider.value = state.isMuted ? 0 : state.volume * 100;
        updateVolumeUI();
    }

    function updateVolumeUI() {
        if (!els.volumeIcon) return;
        const icon = els.volumeIcon.querySelector('i') || els.volumeIcon;
        if (state.isMuted || state.volume === 0) {
            icon.className = 'fas fa-volume-mute';
        } else if (state.volume < 0.5) {
            icon.className = 'fas fa-volume-down';
        } else {
            icon.className = 'fas fa-volume-up';
        }
    }

    // ==================== السحب والإفلات ====================
    function seekTo(e) {
        if (!els.progress || !audio.duration) return;
        const rect = els.progress.getBoundingClientRect();
        const ratio = (e.clientX - rect.left) / rect.width;
        audio.currentTime = ratio * audio.duration;
    }

    // ==================== الخلط والتكرار ====================
    function toggleShuffle() {
        state.isShuffled = !state.isShuffled;
        if (els.shuffle) els.shuffle.classList.toggle('active', state.isShuffled);
    }

    function cycleRepeat() {
        state.repeatMode = (state.repeatMode + 1) % 3;
        if (els.repeat) {
            const icon = els.repeat.querySelector('i') || els.repeat;
            if (state.repeatMode === 0) {
                els.repeat.classList.remove('active');
                icon.className = 'fas fa-redo';
            } else if (state.repeatMode === 1) {
                els.repeat.classList.add('active');
                icon.className = 'fas fa-redo';
            } else {
                els.repeat.classList.add('active');
                icon.className = 'fas fa-redo-alt';
            }
        }
    }

    // ==================== أحداث الصوت ====================
    function onTimeUpdate() {
        if (!audio.duration) return;
        const pct = (audio.currentTime / audio.duration) * 100;
        if (els.progressFill) els.progressFill.style.width = pct + '%';
        if (els.currentTime) els.currentTime.textContent = formatTime(audio.currentTime);
    }

    function onLoaded() {
        if (els.totalTime) els.totalTime.textContent = formatTime(audio.duration);
    }

    function onEnded() {
        if (state.repeatMode === 2) {
            audio.currentTime = 0;
            play();
        } else {
            playNext();
        }
    }

    function onError() {
        showToast('تعذّر تحميل الملف الصوتي', 'error');
    }

    function updatePlayBtn(playing) {
        state.isPlaying = playing;
        if (!els.playPause) return;
        const icon = els.playPause.querySelector('i');
        if (icon) icon.className = playing ? 'fas fa-pause' : 'fas fa-play';
        els.playPause.title = playing ? 'إيقاف' : 'تشغيل';
    }

    // ==================== قائمة التشغيل ====================
    function renderPlaylistItem(track, index) {
        if (!els.playlistItems) return;
        const item = document.createElement('div');
        item.className = 'playlist-item';
        item.dataset.index = index;
        item.innerHTML = `
            <img src="${escapeHtml(track.cover || (window.MusicanConfig ? window.MusicanConfig.defaultCover : ''))}" alt="" loading="lazy">
            <div class="info">
                <div class="title">${escapeHtml(track.title)}</div>
                <div class="performer">${escapeHtml(track.performer || '')}</div>
            </div>
            <span class="duration">${track.duration || ''}</span>
        `;
        item.addEventListener('click', () => playAtIndex(index));
        els.playlistItems.appendChild(item);
    }

    function renderFullPlaylist() {
        if (!els.playlistItems) return;
        els.playlistItems.innerHTML = '';
        state.playlist.forEach((track, i) => renderPlaylistItem(track, i));
        updatePlaylistActive(state.currentIndex);
    }

    function updatePlaylistActive(index) {
        if (!els.playlistItems) return;
        els.playlistItems.querySelectorAll('.playlist-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
    }

    function togglePlaylist() {
        state.playlistOpen = !state.playlistOpen;
        if (els.playlist) els.playlist.classList.toggle('open', state.playlistOpen);
    }

    function closePlaylist(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        state.playlistOpen = false;
        if (els.playlist) els.playlist.classList.remove('open');
    }

    // ==================== إخفاء / إظهار ====================
    function showPlayer() {
        document.body.classList.add('has-player');
        if (els.player) els.player.style.display = 'flex';
    }

    function hidePlayer() {
        document.body.classList.remove('has-player');
        if (els.player) els.player.style.display = 'none';
    }

    function resetDisplay() {
        if (els.title) els.title.textContent = 'اختر مقطعًا للتشغيل';
        if (els.performer) els.performer.textContent = '';
        if (els.coverImg) els.coverImg.src = window.MusicanConfig ? window.MusicanConfig.defaultCover : '';
        if (els.progressFill) els.progressFill.style.width = '0%';
        if (els.currentTime) els.currentTime.textContent = '0:00';
        if (els.totalTime) els.totalTime.textContent = '0:00';
    }

    // ==================== اختصارات ==================== 
    function onKeyDown(e) {
        // لا نتدخل في حقول الإدخال
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

        switch (e.code) {
            case 'Space': e.preventDefault(); togglePlay(); break;
            case 'ArrowRight': audio.currentTime = Math.min(audio.currentTime + 10, audio.duration || 0); break;
            case 'ArrowLeft': audio.currentTime = Math.max(audio.currentTime - 10, 0); break;
            case 'ArrowUp': setVolume(state.volume + 0.1); break;
            case 'ArrowDown': setVolume(state.volume - 0.1); break;
            case 'KeyM': toggleMute(); break;
            case 'KeyN': playNext(); break;
            case 'KeyP': playPrev(); break;
        }
    }

    // ==================== الحفاظ على الحالة ====================
    function saveState() {
        try {
            localStorage.setItem('musican_playlist', JSON.stringify({
                playlist: state.playlist,
                currentIndex: state.currentIndex,
                volume: state.volume,
                repeatMode: state.repeatMode,
                isShuffled: state.isShuffled,
            }));
        } catch (e) { }
    }

    function loadSavedState() {
        try {
            const saved = JSON.parse(localStorage.getItem('musican_playlist') || 'null');
            if (!saved) return;

            state.volume = saved.volume ?? 0.8;
            state.repeatMode = saved.repeatMode ?? 0;
            state.isShuffled = saved.isShuffled ?? false;
            audio.volume = state.volume;
            if (els.volumeSlider) els.volumeSlider.value = state.volume * 100;

            if (saved.playlist && saved.playlist.length > 0) {
                saved.playlist.forEach((t, i) => {
                    state.playlist.push(t);
                    renderPlaylistItem(t, i);
                });

                const idx = saved.currentIndex ?? 0;
                if (idx >= 0 && idx < state.playlist.length) {
                    loadTrack(idx);
                }
                // تمت إزالة showPlayer() من هنا لكي لا يظهر المشغل بشكل مزعج عند فتح الموقع
                // إلا إذا بدأ المستخدم بالتشغيل فعلياً.
            }
        } catch (e) { }
    }

    // ==================== أدوات ====================
    function formatTime(secs) {
        if (isNaN(secs)) return '0:00';
        const m = Math.floor(secs / 60);
        const s = Math.floor(secs % 60);
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function showToast(msg, type = 'info') {
        const container = document.getElementById('toast-container') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fas fa-${type === 'error' ? 'times-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${msg}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);
    }

    function createToastContainer() {
        const c = document.createElement('div');
        c.id = 'toast-container';
        c.className = 'toast-container';
        document.body.appendChild(c);
        return c;
    }

    // ==================== API العامة ====================
    return {
        init,
        addToPlaylist,
        addMultipleToPlaylist,
        clearPlaylist,
        play,
        pause,
        togglePlay,
        playNext,
        playPrev,
        playAtIndex,
        setVolume,
        showPlayer,
        hidePlayer,
        getState: () => ({ ...state }),
        showToast,
    };
})();

// تهيئة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    MusicanPlayer.init();
});

// ==================== أدوات مساعدة عامة ====================

// البحث الفوري
function initSearch(inputId, resultsId, searchUrl) {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);
    if (!input || !results) return;

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results.classList.remove('active'); return; }
        timer = setTimeout(async () => {
            try {
                const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q));
                const data = await res.json();
                renderSearchResults(data, results, input);
            } catch (e) { }
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.remove('active');
        }
    });
}

function renderSearchResults(data, container, input) {
    if (!data.length) {
        container.innerHTML = '<div class="search-result-item"><div class="result-info"><div class="result-name">لا توجد نتائج</div></div></div>';
    } else {
        container.innerHTML = data.map(item => `
            <div class="search-result-item" onclick="window.location='${item.url}'">
                <img src="${item.cover || (window.MusicanConfig ? window.MusicanConfig.defaultCover : '')}" alt="" loading="lazy">
                <div class="result-info">
                    <div class="result-name">${item.name}</div>
                    <div class="result-type">${item.type === 'audio' ? '🎵 مقطع صوتي' : '🎤 مؤدي'}</div>
                </div>
            </div>
        `).join('');
    }
    container.classList.add('active');
}

// قائمة المستخدم
function initUserDropdown() {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-dropdown');
    if (!btn || !menu) return;

    btn.addEventListener('click', e => {
        e.stopPropagation();
        menu.classList.toggle('open');
    });

    document.addEventListener('click', () => menu.classList.remove('open'));
}

// قائمة الموبايل
function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const nav = document.getElementById('site-nav');
    if (!btn || !nav) return;

    btn.addEventListener('click', () => {
        nav.classList.toggle('mobile-open');
        btn.classList.toggle('active');
    });
}

// زر الإعجاب
function initLikeButtons() {
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const audioId = this.dataset.id;
            try {
                const res = await fetch('/api/like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ audio_id: audioId, csrf: document.querySelector('meta[name="csrf"]')?.content }),
                });
                const data = await res.json();
                if (data.success) {
                    this.classList.toggle('liked', data.liked);
                    const count = this.querySelector('.count');
                    if (count) count.textContent = data.count;
                    MusicanPlayer.showToast(data.liked ? '❤️ تم الإعجاب' : 'تم إلغاء الإعجاب', 'success');
                }
            } catch (e) { }
        });
    });
}

// التقييم بالنجوم
function initRatingButtons() {
    document.querySelectorAll('.rate-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const audioId = this.dataset.id;
            const rating = this.dataset.rating;
            try {
                const res = await fetch('/api/rate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ audio_id: audioId, rating, csrf: document.querySelector('meta[name="csrf"]')?.content }),
                });
                const data = await res.json();
                if (data.success) {
                    MusicanPlayer.showToast('⭐ تم التقييم بنجاح', 'success');
                    // تحديث النجوم
                    document.querySelectorAll('.rate-btn').forEach((b, i) => {
                        b.classList.toggle('active', (i + 1) <= rating);
                    });
                }
            } catch (e) { }
        });
    });
}

// متابعة المؤدي
function initFollowButtons() {
    document.querySelectorAll('.follow-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const performerId = this.dataset.id;
            try {
                const res = await fetch('/api/follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ performer_id: performerId, csrf: document.querySelector('meta[name="csrf"]')?.content }),
                });
                const data = await res.json();
                if (data.success) {
                    this.classList.toggle('following', data.following);
                    this.textContent = data.following ? '✓ متابَع' : 'متابعة';
                    MusicanPlayer.showToast(data.following ? '✅ تتابع الآن هذا المؤدي' : 'تم إلغاء المتابعة', 'success');
                }
            } catch (e) { }
        });
    });
}

// تهيئة كل شيء عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    initUserDropdown();
    initMobileMenu();
    initLikeButtons();
    initRatingButtons();
    initFollowButtons();

    // إعداد البحث في الهيدر
    initSearch('header-search-input', 'header-search-results', '/api/search.php');
    initSearch('hero-search-input', 'hero-search-results', '/api/search.php');
});
