/**
 * Musican - JavaScript لوحة التحكم
 */

'use strict';

// ==================== الشريط الجانبي ====================
function initAdminSidebar() {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));

    // إغلاق عند النقر خارجه في الموبايل
    document.addEventListener('click', e => {
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && e.target !== toggle) {
            sidebar.classList.remove('open');
        }
    });

    // القوائم الفرعية القابلة للطي
    document.querySelectorAll('.sidebar-item[data-submenu]').forEach(item => {
        item.addEventListener('click', function () {
            const submenuId = this.dataset.submenu;
            const submenu = document.getElementById(submenuId);
            if (!submenu) return;

            const isOpen = submenu.classList.contains('open');
            // إغلاق كل القوائم الفرعية
            document.querySelectorAll('.sidebar-submenu').forEach(m => m.classList.remove('open'));
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('open'));

            if (!isOpen) {
                submenu.classList.add('open');
                this.classList.add('open');
            }
        });
    });

    // فتح القائمة الفرعية للصفحة الحالية
    const activeSubItem = document.querySelector('.sidebar-subitem.active');
    if (activeSubItem) {
        const submenu = activeSubItem.closest('.sidebar-submenu');
        const parentId = submenu?.id;
        if (submenu) {
            submenu.classList.add('open');
            const parentTrigger = document.querySelector(`[data-submenu="${parentId}"]`);
            if (parentTrigger) parentTrigger.classList.add('open');
        }
    }
}

// ==================== الإشعارات ====================
function initAdminNotifications() {
    const btn = document.getElementById('notif-btn');
    const dropdown = document.getElementById('notif-dropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', async e => {
        e.stopPropagation();
        dropdown.classList.toggle('open');

        if (dropdown.classList.contains('open')) {
            // وضع علامة مقروء
            try {
                await fetch('/admin/api.php?action=mark_read', { method: 'POST' });
                const badge = btn.querySelector('.admin-badge');
                if (badge) badge.remove();
            } catch (e) { }
        }
    });

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('open');
        }
    });
}

// ==================== تأكيد الحذف ====================
function confirmDelete(message, formId) {
    if (confirm(message || 'هل أنت متأكد من الحذف؟ لا يمكن التراجع عن هذا الإجراء.')) {
        if (formId) {
            document.getElementById(formId)?.submit();
        }
        return true;
    }
    return false;
}

// ==================== معاينة الصور ====================
function initImagePreviews() {
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (!preview || !this.files[0]) return;

            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; };
            reader.readAsDataURL(this.files[0]);
        });
    });
}

// ==================== العد التنازلي للأحرف ====================
function initCharCounters() {
    document.querySelectorAll('[data-max-length]').forEach(el => {
        const max = parseInt(el.dataset.maxLength);
        const counter = document.getElementById(el.id + '-counter');
        if (!counter) return;

        const update = () => {
            const remaining = max - el.value.length;
            counter.textContent = remaining;
            counter.style.color = remaining < 20 ? '#e74c3c' : '#808098';
        };

        el.addEventListener('input', update);
        update();
    });
}

// ==================== البحث في الجداول ====================
function initTableSearch() {
    document.querySelectorAll('.table-search').forEach(input => {
        const tableId = input.dataset.table;
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    });
}

// ==================== التبويبات ====================
function initTabs() {
    document.querySelectorAll('.admin-tabs').forEach(tabs => {
        tabs.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const target = this.dataset.tab;
                tabs.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                document.querySelectorAll('.tab-content').forEach(c => {
                    c.classList.toggle('active', c.id === target);
                    c.style.display = c.id === target ? 'block' : 'none';
                });
            });
        });

        // تفعيل أول تبويب
        const firstTab = tabs.querySelector('.admin-tab');
        if (firstTab && !tabs.querySelector('.admin-tab.active')) {
            firstTab.click();
        }
    });
}

// ==================== انتقاء الكل ====================
function initSelectAll() {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });
}

// ==================== لوحة التحكم الإحصائية ====================
function animateNumbers() {
    document.querySelectorAll('.stat-card-value[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count);
        const duration = 1500;
        const start = Date.now();
        const startVal = 0;

        function update() {
            const elapsed = Date.now() - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            el.textContent = Math.floor(startVal + (target - startVal) * eased).toLocaleString('ar');
            if (progress < 1) requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    });
}

// ==================== إرسال الفورم بـ AJAX ====================
function initAjaxForms() {
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn?.textContent;

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'جاري المعالجة...';
            }

            try {
                const res = await fetch(this.action, {
                    method: this.method || 'POST',
                    body: new FormData(this),
                });
                const data = await res.json();

                if (data.success) {
                    showAdminToast(data.message || 'تمت العملية بنجاح', 'success');
                    if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    showAdminToast(data.message || 'حدث خطأ', 'error');
                }
            } catch (e) {
                showAdminToast('خطأ في الاتصال بالخادم', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        });
    });
}

// ==================== التنبيهات المنبثقة ====================
function showAdminToast(message, type = 'info') {
    const container = document.getElementById('toast-container') || (() => {
        const c = document.createElement('div');
        c.id = 'toast-container';
        c.style.cssText = 'position:fixed;top:80px;left:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(c);
        return c;
    })();

    const icons = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-circle', info: 'info-circle' };
    const colors = { success: '#27ae60', error: '#e74c3c', warning: '#f39c12', info: '#3498db' };

    const toast = document.createElement('div');
    toast.style.cssText = `
        padding: 12px 20px;
        background: ${colors[type] || colors.info};
        border-radius: 8px;
        color: white;
        font-size: 0.9rem;
        font-family: Cairo, sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        transform: translateX(-100px);
        opacity: 0;
        transition: all 0.4s;
        max-width: 320px;
        direction: rtl;
    `;
    toast.innerHTML = `<i class="fas fa-${icons[type]}"></i> ${message}`;
    container.appendChild(toast);

    setTimeout(() => { toast.style.transform = 'translateX(0)'; toast.style.opacity = '1'; }, 10);
    setTimeout(() => {
        toast.style.transform = 'translateX(-100px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

// ==================== التهيئة العامة ====================
document.addEventListener('DOMContentLoaded', () => {
    initAdminSidebar();
    initAdminNotifications();
    initImagePreviews();
    initCharCounters();
    initTableSearch();
    initTabs();
    initSelectAll();
    animateNumbers();
    initAjaxForms();
});
