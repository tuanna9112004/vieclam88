import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;
Alpine.start();

// Disable a [data-submit-once] button right after its form submits — hạn chế double-click gửi
// trùng (ví dụ form ứng tuyển). Không chặn submit, chỉ vô hiệu hoá nút sau khi trình duyệt đã
// bắt đầu gửi request, và hiện lại nếu validation phía server thất bại (trang không reload SPA).
document.addEventListener('submit', (event) => {
    const button = event.target.querySelector?.('[data-submit-once]');

    if (!button) {
        return;
    }

    window.setTimeout(() => {
        button.disabled = true;
        button.dataset.originalLabel = button.textContent;
        button.textContent = 'Đang gửi...';
    }, 0);
});
