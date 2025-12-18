/**
 * Wespee Profile Web Interface - Main JavaScript
 */

// QR Code instance
let qrCodeInstance = null;

/**
 * Show toast notification
 */
function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    if (!toast || !toastMessage) return;

    toastMessage.textContent = message;
    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, duration);
}

/**
 * Copy profile link to clipboard
 */
async function copyProfileLink() {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(profileData.profileUrl);
            showToast('Le lien du profil a été copié');
        } else {
            // Fallback for older browsers or non-HTTPS
            const textArea = document.createElement('textarea');
            textArea.value = profileData.profileUrl;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();

            try {
                document.execCommand('copy');
                showToast('Le lien du profil a été copié');
            } catch (err) {
                showToast('Erreur lors de la copie du lien');
            } finally {
                document.body.removeChild(textArea);
            }
        }
    } catch (error) {
        console.error('Error copying to clipboard:', error);
        showToast('Erreur lors de la copie du lien');
    }
}

/**
 * Share profile using Web Share API or fallback to copy
 */
async function shareProfile() {
    // Check if Web Share API is supported
    if (navigator.share) {
        try {
            await navigator.share({
                title: profileData.fullName,
                text: `Envoyez de l'argent à ${profileData.fullName} sur Wespee`,
                url: profileData.profileUrl
            });
        } catch (error) {
            // User cancelled share or error occurred
            if (error.name !== 'AbortError') {
                console.error('Error sharing:', error);
                // Fallback to copy
                await copyProfileLink();
            }
        }
    } else {
        // Fallback: copy to clipboard
        await copyProfileLink();
    }
}

/**
 * Show QR code bottom sheet with image from API
 */
function showQRCode() {
    const modal = document.getElementById('qrModal');
    const bottomSheet = document.getElementById('qrBottomSheet');
    const qrContainer = document.getElementById('qrcode');

    if (!modal || !bottomSheet || !qrContainer) return;

    // Clear previous QR code
    qrContainer.innerHTML = '';

    // Display QR code from API (base64 image)
    try {
        if (profileData.qrCodeData) {
            const img = document.createElement('img');
            img.src = profileData.qrCodeData;
            img.alt = 'QR Code';
            img.className = 'w-full h-full';
            qrContainer.appendChild(img);
        } else {
            // Fallback: generate QR code if API doesn't provide one
            // Use deep link format
            qrCodeInstance = new QRCode(qrContainer, {
                text: `https://asset.wespee.me/${profileData.username}`,
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        // Show modal backdrop
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling

        // Animate bottom sheet sliding up
        setTimeout(() => {
            bottomSheet.classList.remove('translate-y-full');
            bottomSheet.classList.add('translate-y-0');
        }, 10);
    } catch (error) {
        console.error('Error displaying QR code:', error);
        showToast('Erreur lors de l\'affichage du QR code');
    }
}

/**
 * Close QR code bottom sheet
 */
function closeQRModal(event) {
    // Close if clicking backdrop or explicitly calling
    if (!event || event.target.id === 'qrModal') {
        const modal = document.getElementById('qrModal');
        const bottomSheet = document.getElementById('qrBottomSheet');

        if (modal && bottomSheet) {
            // Animate bottom sheet sliding down
            bottomSheet.classList.remove('translate-y-0');
            bottomSheet.classList.add('translate-y-full');

            // Hide modal backdrop after animation completes
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = ''; // Restore scrolling
            }, 300); // Match transition duration
        }
    }
}

/**
 * Detect mobile device type
 */
function getMobileOS() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;

    // iOS detection
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        return 'iOS';
    }

    // Android detection
    if (/android/i.test(userAgent)) {
        return 'Android';
    }

    return 'other';
}

/**
 * Attempt to open the app via deep link, fallback to app store
 */
function payNow() {
    const deepLink = `https://asset.wespee.me/${profileData.username}`;
    const universalLink = deepLink; // Use deep link as universal link
    const mobileOS = getMobileOS();

    // Try to open the app
    const startTime = Date.now();
    let appOpened = false;

    // Listen for visibility change (indicates app was opened)
    const handleVisibilityChange = () => {
        if (document.hidden) {
            appOpened = true;
        }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Try to open the app
    if (mobileOS === 'iOS') {
        // iOS: Try universal link first, then custom scheme
        window.location.href = universalLink;

        setTimeout(() => {
            if (!appOpened && !document.hidden) {
                window.location.href = deepLink;
            }
        }, 100);
    } else if (mobileOS === 'Android') {
        // Android: Try custom scheme
        window.location.href = deepLink;
    } else {
        // Desktop: Show app download modal
        showAppDownloadModal();
        return;
    }

    // Fallback to app store if app is not installed
    setTimeout(() => {
        document.removeEventListener('visibilitychange', handleVisibilityChange);

        if (!appOpened && !document.hidden) {
            const elapsedTime = Date.now() - startTime;

            // If less than 2 seconds passed, app likely didn't open
            if (elapsedTime < 2000) {
                redirectToAppStore(mobileOS);
            }
        }
    }, 1500);
}

/**
 * Redirect to appropriate app store
 */
function redirectToAppStore(mobileOS) {
    if (mobileOS === 'iOS' && profileData.iosAppStoreUrl) {
        window.location.href = profileData.iosAppStoreUrl;
    } else if (mobileOS === 'Android' && profileData.androidPlayStoreUrl) {
        window.location.href = profileData.androidPlayStoreUrl;
    } else {
        // No app store URL configured or unknown OS
        showAppDownloadModal();
    }
}

/**
 * Show app download modal for desktop users
 */
function showAppDownloadModal() {
    const message = profileData.iosAppStoreUrl || profileData.androidPlayStoreUrl
        ? 'Téléchargez l\'application Wespee pour continuer'
        : 'Cette fonctionnalité est disponible uniquement sur l\'application mobile Wespee';

    // Create modal HTML
    const modalHTML = `
        <div id="downloadModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="closeDownloadModal(event)">
            <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl" onclick="event.stopPropagation()">
                <div class="flex justify-center mb-6">
                    <svg class="w-20 h-20 text-wespee-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 text-center mb-4">Téléchargez Wespee</h2>
                <p class="text-gray-600 text-center mb-6">${message}</p>
                <div class="space-y-3">
                    ${profileData.iosAppStoreUrl ? `
                        <a href="${profileData.iosAppStoreUrl}" class="block w-full py-3 bg-gray-900 text-white font-medium rounded-full text-center hover:bg-gray-800 transition-colors">
                            Télécharger sur l'App Store
                        </a>
                    ` : ''}
                    ${profileData.androidPlayStoreUrl ? `
                        <a href="${profileData.androidPlayStoreUrl}" class="block w-full py-3 bg-wespee-green text-gray-900 font-medium rounded-full text-center hover:opacity-90 transition-opacity">
                            Télécharger sur Google Play
                        </a>
                    ` : ''}
                    <button onclick="closeDownloadModal()" class="w-full py-3 bg-gray-100 text-gray-700 font-medium rounded-full hover:bg-gray-200 transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHTML;
    document.body.appendChild(modalContainer.firstElementChild);
    document.body.style.overflow = 'hidden';
}

/**
 * Close app download modal
 */
function closeDownloadModal(event) {
    if (!event || event.target.id === 'downloadModal') {
        const modal = document.getElementById('downloadModal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    }
}

/**
 * Handle keyboard shortcuts
 */
document.addEventListener('keydown', (event) => {
    // Close modal on Escape key
    if (event.key === 'Escape') {
        closeQRModal();
        closeDownloadModal();
    }
});

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('Wespee Profile Page loaded');
    console.log('Profile:', profileData);

    // Preload QR code library if needed
    if (typeof QRCode === 'undefined') {
        console.warn('QRCode library not loaded');
    }
});

/**
 * Handle page visibility for deep link detection
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('Page hidden - app may have opened');
    } else {
        console.log('Page visible - returned to browser');
    }
});
