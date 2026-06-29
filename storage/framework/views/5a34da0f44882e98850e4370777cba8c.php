<?php if (! $__env->hasRenderedOnce('53c22492-f55a-457a-b015-48537c1c54af')): $__env->markAsRenderedOnce('53c22492-f55a-457a-b015-48537c1c54af'); ?>
    <?php $__env->startPush('styles'); ?>
        <style>
            .package-download-overlay {
                position: fixed;
                inset: 0;
                z-index: 2050;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: rgba(15, 23, 42, 0.58);
                backdrop-filter: blur(10px);
            }

            .package-download-overlay.is-visible {
                display: flex;
                animation: packageOverlayFade .22s ease-out both;
            }

            .package-download-panel {
                width: min(440px, 100%);
                border-radius: 28px;
                padding: 28px;
                border: 1px solid rgba(255, 255, 255, .38);
                background: linear-gradient(145deg, rgba(255, 255, 255, .96), rgba(240, 247, 255, .94));
                box-shadow: 0 28px 80px rgba(15, 23, 42, .32);
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .package-download-panel::before,
            .package-download-panel::after {
                content: '';
                position: absolute;
                width: 160px;
                height: 160px;
                border-radius: 999px;
                filter: blur(1px);
                opacity: .55;
            }

            .package-download-panel::before {
                top: -74px;
                right: -58px;
                background: rgba(59, 130, 246, .17);
            }

            .package-download-panel::after {
                bottom: -92px;
                left: -60px;
                background: rgba(16, 185, 129, .14);
            }

            .package-download-cloud {
                position: relative;
                z-index: 1;
                width: 104px;
                height: 104px;
                margin: 0 auto 18px;
                border-radius: 32px;
                display: grid;
                place-items: center;
                color: #0f766e;
                background: radial-gradient(circle at 30% 20%, #ecfeff, #dbeafe 65%, #ccfbf1);
                box-shadow: inset 0 0 0 1px rgba(14, 165, 233, .16), 0 18px 38px rgba(14, 116, 144, .16);
            }

            .package-download-cloud i {
                font-size: 2.6rem;
                animation: packageCloudFloat 1.6s ease-in-out infinite;
            }

            .package-download-ring {
                position: absolute;
                inset: -8px;
                border-radius: 36px;
                border: 3px solid rgba(14, 165, 233, .14);
                border-top-color: rgba(14, 165, 233, .78);
                animation: packageRingSpin .95s linear infinite;
            }

            .package-download-title,
            .package-download-message,
            .package-download-dots {
                position: relative;
                z-index: 1;
            }

            .package-download-title {
                font-weight: 800;
                font-size: 1.1rem;
                color: #0f172a;
                margin-bottom: 6px;
            }

            .package-download-message {
                color: #64748b;
                font-size: .92rem;
                line-height: 1.55;
                margin: 0 auto;
                max-width: 330px;
            }

            .package-download-dots {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 18px;
            }

            .package-download-dots span {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                background: #2563eb;
                animation: packageDotPulse 1s ease-in-out infinite;
            }

            .package-download-dots span:nth-child(2) { animation-delay: .14s; background: #0ea5e9; }
            .package-download-dots span:nth-child(3) { animation-delay: .28s; background: #14b8a6; }

            .js-package-download.is-loading {
                pointer-events: none;
                opacity: .86;
            }

            @keyframes packageOverlayFade {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes packageRingSpin {
                to { transform: rotate(360deg); }
            }

            @keyframes packageCloudFloat {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-6px); }
            }

            @keyframes packageDotPulse {
                0%, 80%, 100% { transform: translateY(0); opacity: .42; }
                40% { transform: translateY(-7px); opacity: 1; }
            }
        </style>
    <?php $__env->stopPush(); ?>

    <div class="package-download-overlay" id="packageDownloadOverlay" aria-live="polite" aria-hidden="true">
        <div class="package-download-panel">
            <div class="package-download-cloud">
                <span class="package-download-ring"></span>
                <i class="bi bi-cloud-arrow-down"></i>
            </div>
            <div class="package-download-title" id="packageDownloadTitle">Menyiapkan Paket Rilis</div>
            <p class="package-download-message" id="packageDownloadMessage">
                Sistem sedang membungkus file lokal dan mengambil dokumen dari link eksternal yang dapat diakses.
            </p>
            <div class="package-download-dots" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const overlay = document.getElementById('packageDownloadOverlay');
                const overlayTitle = document.getElementById('packageDownloadTitle');
                const overlayMessage = document.getElementById('packageDownloadMessage');

                const resetDownloadButtons = function () {
                    document.querySelectorAll('.js-package-download.is-loading').forEach(function (button) {
                        if (button.dataset.originalHtml) {
                            button.innerHTML = button.dataset.originalHtml;
                        }
                        button.classList.remove('is-loading', 'disabled');
                        button.removeAttribute('aria-disabled');
                    });

                    if (overlay) {
                        overlay.classList.remove('is-visible');
                        overlay.setAttribute('aria-hidden', 'true');
                    }
                };

                document.querySelectorAll('.js-package-download').forEach(function (button) {
                    button.addEventListener('click', function () {
                        button.dataset.originalHtml = button.innerHTML;
                        button.classList.add('is-loading', 'disabled');
                        button.setAttribute('aria-disabled', 'true');
                        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyiapkan...';

                        if (overlay) {
                            overlayTitle.textContent = button.dataset.loadingTitle || 'Menyiapkan Paket Rilis';
                            overlayMessage.textContent = button.dataset.loadingMessage || 'Sistem sedang membungkus file lokal dan mengambil dokumen dari link eksternal yang dapat diakses.';
                            overlay.classList.add('is-visible');
                            overlay.setAttribute('aria-hidden', 'false');
                        }

                        window.setTimeout(resetDownloadButtons, 12000);
                    });
                });

                window.addEventListener('pageshow', resetDownloadButtons);
            });
        </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/shared/_package_download_loader.blade.php ENDPATH**/ ?>