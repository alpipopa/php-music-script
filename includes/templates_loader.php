<?php
/**
 * محمّل القوالب
 */

if (!defined('MUSICAN_APP')) die('Access Denied');

function getActiveTemplate(): string {
    return getSetting('active_template', 'default');
}

function getTemplatePath(string $template = ''): string {
    if (empty($template)) $template = getActiveTemplate();
    return ROOT_PATH . '/templates/' . $template . '/';
}

function getTemplateUrl(string $template = ''): string {
    if (empty($template)) $template = getActiveTemplate();
    return BASE_URL . '/templates/' . $template . '/';
}

function loadTemplateStyle(): string {
    $template = getActiveTemplate();
    $cssFile = getTemplatePath($template) . 'style.css';
    if (file_exists($cssFile)) {
        return '<link rel="stylesheet" href="' . getTemplateUrl($template) . 'style.css?v=' . filemtime($cssFile) . '">';
    }
    return '';
}
