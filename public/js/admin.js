/**
 * Script Interaktif Dashboard Admin Operasional - Shuttle Kampus (Kelompok 5)
 * File: public/js/admin.js
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Modal Edit Halte
    const stopModal = document.getElementById('modal-edit-stop');
    const stopForm = document.getElementById('form-edit-stop');
    const stopTitle = document.getElementById('modal-stop-title');
    const stopOrderInput = document.getElementById('modal_stop_order');
    const stopNameInput = document.getElementById('modal_stop_name');
    const stopLatInput = document.getElementById('modal_stop_lat');
    const stopLngInput = document.getElementById('modal_stop_lng');

    // 2. Inisialisasi Modal Edit Armada
    const shuttleModal = document.getElementById('modal-edit-shuttle');
    const shuttleForm = document.getElementById('form-edit-shuttle');
    const shuttleTitle = document.getElementById('modal-shuttle-title');
    const shuttleCodeInput = document.getElementById('modal_shuttle_code');
    const shuttlePlateInput = document.getElementById('modal_shuttle_plate');
    const shuttleDriverInput = document.getElementById('modal_shuttle_driver');
    const shuttleCapacityInput = document.getElementById('modal_shuttle_capacity');
    const shuttleStatusInput = document.getElementById('modal_shuttle_status');

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    // Event Listener Tombol Edit Halte di Setiap Baris Tabel
    document.querySelectorAll('.btn-trigger-edit-stop').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const order = this.getAttribute('data-order');
            const name = this.getAttribute('data-name');
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');
            const action = this.getAttribute('data-action');

            if (stopForm && action) {
                stopForm.action = action;
            }
            if (stopTitle) {
                stopTitle.textContent = '✏️ Edit Data Halte: #' + order + ' — ' + name;
            }
            if (stopOrderInput) stopOrderInput.value = order || '1';
            if (stopNameInput) stopNameInput.value = name || '';
            if (stopLatInput) stopLatInput.value = lat || '';
            if (stopLngInput) stopLngInput.value = lng || '';

            openModal(stopModal);
            setTimeout(function () {
                if (stopNameInput) stopNameInput.focus();
            }, 100);
        });
    });

    // Event Listener Tombol Edit Armada di Setiap Baris Tabel
    document.querySelectorAll('.btn-trigger-edit-shuttle').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const code = this.getAttribute('data-code');
            const plate = this.getAttribute('data-plate');
            const driver = this.getAttribute('data-driver');
            const capacity = this.getAttribute('data-capacity');
            const status = this.getAttribute('data-status');
            const action = this.getAttribute('data-action');

            if (shuttleForm && action) {
                shuttleForm.action = action;
            }
            if (shuttleTitle) {
                shuttleTitle.textContent = '✏️ Edit Data Armada: ' + code + ' (' + plate + ')';
            }
            if (shuttleCodeInput) shuttleCodeInput.value = code || '';
            if (shuttlePlateInput) shuttlePlateInput.value = plate || '';
            if (shuttleDriverInput) shuttleDriverInput.value = driver || '';
            if (shuttleCapacityInput) shuttleCapacityInput.value = capacity || '12';
            if (shuttleStatusInput) shuttleStatusInput.value = status || 'active';

            openModal(shuttleModal);
            setTimeout(function () {
                if (shuttleDriverInput) shuttleDriverInput.focus();
            }, 100);
        });
    });

    // Tutup Modal via Tombol X, Batal, atau Klik Backdrop Luar
    document.querySelectorAll('[data-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = this.closest('.admin-modal');
            closeModal(modal);
        });
    });

    document.querySelectorAll('.admin-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function () {
            const modal = this.closest('.admin-modal');
            closeModal(modal);
        });
    });

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.admin-modal.active').forEach(function (modal) {
                closeModal(modal);
            });
        }
    });

    // Otomatis scroll ke seksi jika diarahkan dari aksi flash redirect
    const tracker = document.getElementById('section-tracker');
    const activeSection = tracker ? tracker.getAttribute('data-active-section') : document.body.getAttribute('data-active-section');
    if (activeSection) {
        const targetElement = document.getElementById(activeSection);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } else if (window.location.hash) {
        const hashElement = document.querySelector(window.location.hash);
        if (hashElement) {
            hashElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
});
