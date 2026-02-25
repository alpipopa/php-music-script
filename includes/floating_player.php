<?php
/**
 * مشغل الصوتيات العائم
 * Floating Audio Player HTML
 */

if (!defined('MUSICAN_APP')) die('Access Denied');
?>

<!-- ===== قائمة التشغيل العائمة ===== -->
<div class="playlist-panel" id="musican-playlist">
    <div class="playlist-header">
        <span><i class="fas fa-list-music"></i> قائمة التشغيل</span>
        <button type="button" class="player-btn" id="btn-close-playlist" title="إغلاق">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="playlist-items"></div>
</div>

<!-- ===== المشغل الرئيسي ===== -->
<div class="mini-player-bar" id="musican-player" style="display:none;">

    <!-- معلومات المقطع -->
    <div class="player-track-info">
        <img class="player-cover" id="player-cover" src="<?= DEFAULT_COVER_URL ?>" alt="">
        <div class="player-meta">
            <div class="player-title" id="player-title">اختر مقطعًا للتشغيل</div>
            <div class="player-performer" id="player-performer"></div>
        </div>
    </div>

    <!-- أزرار التحكم -->
    <div class="player-controls">
        <div class="player-btns">
            <button class="player-btn" id="btn-shuffle" title="تشغيل عشوائي">
                <i class="fas fa-random"></i>
            </button>
            <button class="player-btn" id="btn-prev" title="السابق">
                <i class="fas fa-step-backward"></i>
            </button>
            <button class="player-btn play-pause-btn" id="btn-play-pause" title="تشغيل">
                <i class="fas fa-play"></i>
            </button>
            <button class="player-btn" id="btn-next" title="التالي">
                <i class="fas fa-step-forward"></i>
            </button>
            <button class="player-btn" id="btn-repeat" title="تكرار">
                <i class="fas fa-redo"></i>
            </button>
        </div>

        <!-- شريط التقدم -->
        <div class="player-progress">
            <span class="player-time" id="current-time">0:00</span>
            <div class="progress-bar" id="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <span class="player-time" id="total-time">0:00</span>
        </div>
    </div>

    <!-- الأدوات الإضافية -->
    <div class="player-extras">
        <!-- التحكم في الصوت -->
        <div class="volume-control">
            <button class="player-btn" id="btn-volume" title="الصوت">
                <i class="fas fa-volume-up"></i>
            </button>
            <input type="range" class="volume-slider" id="volume-slider" min="0" max="100" value="80" title="الصوت">
        </div>

        <!-- زر قائمة التشغيل -->
        <button class="player-btn" id="btn-toggle-playlist" title="قائمة التشغيل">
            <i class="fas fa-list"></i>
        </button>

        <!-- زر إخفاء المشغل -->
        <button class="player-btn" id="btn-hide-player" title="إخفاء المشغل">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
