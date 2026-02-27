<?php
/**
 * مكون بطاقة المقطع الصوتي
 * Audio Card Component
 */
?>
<div class="audio-card" onclick="playAudioCard(<?= $audio['id'] ?>, '<?= addslashes($audio['title']) ?>', '<?= addslashes($audio['performer_name']) ?>', '<?= addslashes(getImageUrl('albums', $audio['cover_image'] ?? '')) ?>', '<?= addslashes(getAudioUrl($audio['audio_file'])) ?>', '<?= addslashes($audio['duration'] ?? '') ?>')">
    <div class="audio-card-img">
        <img src="<?= getImageUrl('albums', $audio['cover_image'] ?? '') ?>" alt="<?= clean($audio['title']) ?>" loading="lazy">
        <div class="audio-card-play">
            <div class="play-btn-circle"><i class="fas fa-play"></i></div>
        </div>
        <?php if ($audio['is_featured']): ?>
            <span class="audio-card-badge">مميز</span>
        <?php endif; ?>
    </div>
    <div class="audio-card-body">
        <div class="audio-card-title" title="<?= clean($audio['title']) ?>"><?= clean($audio['title']) ?></div>
        <div class="audio-card-meta">
            <span class="performer-name"><?= clean($audio['performer_name']) ?></span>
            <span class="duration"><?= formatDuration($audio['duration'] ?? 0) ?></span>
        </div>
    </div>
</div>
