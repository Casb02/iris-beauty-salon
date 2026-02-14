import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('[data-site-header]');
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const barOne = document.querySelector('[data-bar-one]');
    const barTwo = document.querySelector('[data-bar-two]');
    const barThree = document.querySelector('[data-bar-three]');
    const mobileLinks = document.querySelectorAll('[data-mobile-menu] a');

    const syncHeaderState = () => {
        if (!header) {
            return;
        }

        if (window.scrollY > 50) {
            header.classList.add('bg-white/98', 'backdrop-blur-sm', 'shadow-sm', 'py-3');
            header.classList.remove('py-6');
            return;
        }

        header.classList.remove('bg-white/98', 'backdrop-blur-sm', 'shadow-sm', 'py-3');
        header.classList.add('py-6');
    };

    const setMobileMenuState = (isOpen) => {
        if (!menuToggle || !mobileMenu || !barOne || !barTwo || !barThree) {
            return;
        }

        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileMenu.classList.toggle('max-h-[400px]', isOpen);
        mobileMenu.classList.toggle('opacity-100', isOpen);
        mobileMenu.classList.toggle('max-h-0', !isOpen);
        mobileMenu.classList.toggle('opacity-0', !isOpen);
        barOne.classList.toggle('translate-y-2', isOpen);
        barOne.classList.toggle('rotate-45', isOpen);
        barTwo.classList.toggle('opacity-0', isOpen);
        barThree.classList.toggle('-translate-y-2', isOpen);
        barThree.classList.toggle('-rotate-45', isOpen);
    };

    let mobileMenuOpen = false;

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            mobileMenuOpen = !mobileMenuOpen;
            setMobileMenuState(mobileMenuOpen);
        });
    }

    mobileLinks.forEach((link) => {
        link.addEventListener('click', () => {
            mobileMenuOpen = false;
            setMobileMenuState(false);
        });
    });

    window.addEventListener('scroll', syncHeaderState, { passive: true });
    syncHeaderState();
    setMobileMenuState(false);

    const bookingUrl = 'https://iris-beauty-salon.salonized.com/widget_bookings/new';
    const confirmDialog = document.querySelector('[data-booking-confirm]');
    const confirmPanel = document.querySelector('[data-booking-confirm-panel]');
    const bookingModal = document.querySelector('[data-booking-modal]');
    const bookingPanel = document.querySelector('[data-booking-modal-panel]');
    const bookingIframe = document.querySelector('[data-booking-iframe]');
    const bookingTriggers = document.querySelectorAll('[data-booking-trigger]');
    const acceptButton = document.querySelector('[data-booking-accept]');
    const cancelButton = document.querySelector('[data-booking-cancel]');
    const closeButton = document.querySelector('[data-booking-close]');
    const modalAnimationDuration = 320;
    const closingTimers = new WeakMap();
    const overlayHiddenClasses = ['opacity-0', 'pointer-events-none'];
    const overlayVisibleClasses = ['opacity-100', 'pointer-events-auto'];
    const panelHiddenClasses = ['translate-y-6', 'scale-[0.98]', 'opacity-0'];
    const panelVisibleClasses = ['translate-y-0', 'scale-100', 'opacity-100'];
    let confirmDialogOpen = false;
    let bookingModalOpen = false;
    let iframeResetTimer = null;

    const syncScrollLock = () => {
        document.body.style.overflow = confirmDialogOpen || bookingModalOpen ? 'hidden' : '';
    };

    const clearClosingTimer = (overlay) => {
        const timer = closingTimers.get(overlay);

        if (timer) {
            window.clearTimeout(timer);
            closingTimers.delete(overlay);
        }
    };

    const showLayer = (overlay, panel) => {
        if (!overlay || !panel) {
            return;
        }

        clearClosingTimer(overlay);
        overlay.classList.remove('hidden');

        window.requestAnimationFrame(() => {
            overlay.classList.remove(...overlayHiddenClasses);
            overlay.classList.add(...overlayVisibleClasses);
            panel.classList.remove(...panelHiddenClasses);
            panel.classList.add(...panelVisibleClasses);
        });
    };

    const hideLayer = (overlay, panel) => {
        if (!overlay || !panel) {
            return;
        }

        clearClosingTimer(overlay);
        overlay.classList.remove(...overlayVisibleClasses);
        overlay.classList.add(...overlayHiddenClasses);
        panel.classList.remove(...panelVisibleClasses);
        panel.classList.add(...panelHiddenClasses);

        const timer = window.setTimeout(() => {
            overlay.classList.add('hidden');
        }, modalAnimationDuration);

        closingTimers.set(overlay, timer);
    };

    const showConfirmDialog = () => {
        if (!confirmDialog || !confirmPanel) {
            return;
        }

        confirmDialogOpen = true;
        showLayer(confirmDialog, confirmPanel);
        syncScrollLock();
    };

    const hideConfirmDialog = () => {
        if (!confirmDialog || !confirmPanel) {
            return;
        }

        confirmDialogOpen = false;
        hideLayer(confirmDialog, confirmPanel);
        syncScrollLock();
    };

    const openBookingModal = () => {
        if (!bookingModal || !bookingPanel || !bookingIframe) {
            return;
        }

        if (iframeResetTimer) {
            window.clearTimeout(iframeResetTimer);
            iframeResetTimer = null;
        }

        confirmDialogOpen = false;
        hideLayer(confirmDialog, confirmPanel);
        bookingIframe.src = bookingUrl;
        bookingModalOpen = true;
        showLayer(bookingModal, bookingPanel);
        syncScrollLock();
    };

    const closeBookingModal = () => {
        if (!bookingModal || !bookingPanel || !bookingIframe) {
            return;
        }

        bookingModalOpen = false;
        hideLayer(bookingModal, bookingPanel);
        syncScrollLock();

        if (iframeResetTimer) {
            window.clearTimeout(iframeResetTimer);
        }

        iframeResetTimer = window.setTimeout(() => {
            bookingIframe.src = 'about:blank';
        }, modalAnimationDuration);
    };

    bookingTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            mobileMenuOpen = false;
            setMobileMenuState(false);
            showConfirmDialog();
        });
    });

    if (acceptButton) {
        acceptButton.addEventListener('click', openBookingModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', hideConfirmDialog);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeBookingModal);
    }

    if (confirmDialog) {
        confirmDialog.addEventListener('click', (event) => {
            if (event.target === confirmDialog) {
                hideConfirmDialog();
            }
        });
    }

    if (bookingModal) {
        bookingModal.addEventListener('click', (event) => {
            if (event.target === bookingModal) {
                closeBookingModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (bookingModalOpen) {
            closeBookingModal();
            return;
        }

        if (confirmDialogOpen) {
            hideConfirmDialog();
        }
    });
});
