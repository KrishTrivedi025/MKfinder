// Global variables
let selectedFile = null;
let isUploading = false;
let currentUser = null;

// DOM elements - Main App
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('imageInput');
const previewSection = document.getElementById('previewSection');
const previewImage = document.getElementById('previewImage');
const identifyBtn = document.getElementById('identifyBtn');
const resetBtn = document.getElementById('resetBtn');
const loadingSection = document.getElementById('loadingSection');
const resultsSection = document.getElementById('resultsSection');
const resultsContent = document.getElementById('resultsContent');
const errorSection = document.getElementById('errorSection');
const errorContent = document.getElementById('errorContent');

// DOM elements - Authentication
const authSection = document.getElementById('auth-section');
const mainApp = document.getElementById('main-app');
const welcomeCard = document.getElementById('welcome-card');
const loginCard = document.getElementById('login-card');
const signupCard = document.getElementById('signup-card');
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const loginError = document.getElementById('loginError');
const signupError = document.getElementById('signupError');
const userEmail = document.getElementById('userEmail');
const loadingOverlay = document.getElementById('loadingOverlay');

// ── MK MODAL SYSTEM — replaces all browser alert/confirm ────────────────────
// Single reusable confirm modal used everywhere in the app
function showMKConfirm(icon, iconColor, title, message, confirmText, cancelText, onConfirm) {
    let modal = document.getElementById('mkConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'mkConfirmModal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.85);backdrop-filter:blur(8px);';
        document.body.appendChild(modal);
    }
    modal.innerHTML = `
        <div style="background:linear-gradient(135deg,#0d1b2a,#0f2236);border:1px solid rgba(116,198,157,.2);
                    border-radius:20px;padding:36px 32px;max-width:380px;width:100%;text-align:center;
                    box-shadow:0 32px 80px rgba(0,0,0,.5);animation:mkModalIn .25s ease;">
            <div style="width:60px;height:60px;border-radius:50%;margin:0 auto 16px;
                         background:${iconColor}18;border:2px solid ${iconColor}44;
                         display:flex;align-items:center;justify-content:center;">
                <i class="${icon}" style="font-size:1.4rem;color:${iconColor};"></i>
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;
                        color:#fff;margin-bottom:8px;">${title}</div>
            <div style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:24px;">
                ${message}
            </div>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button id="mkConfirmYes" style="flex:1;max-width:160px;background:linear-gradient(135deg,#e63946,#f87171);
                    color:#fff;border:none;border-radius:50px;padding:11px 20px;font-size:.85rem;
                    font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:transform .2s;"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='none'">${confirmText}</button>
                <button id="mkConfirmNo" style="flex:1;max-width:160px;background:rgba(116,198,157,.12);
                    color:#74c69d;border:1px solid rgba(116,198,157,.25);border-radius:50px;
                    padding:11px 20px;font-size:.85rem;font-weight:600;cursor:pointer;
                    font-family:'Inter',sans-serif;transition:transform .2s;"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='none'">${cancelText}</button>
            </div>
        </div>
        <style>@keyframes mkModalIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    const close = () => { modal.style.display = 'none'; document.body.style.overflow = ''; };
    document.getElementById('mkConfirmYes').onclick = () => { close(); onConfirm(); };
    document.getElementById('mkConfirmNo').onclick  = close;
    modal.onclick = (e) => { if (e.target === modal) close(); };
}

function showMKAlert(icon, iconColor, title, message) {
    let modal = document.getElementById('mkAlertModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'mkAlertModal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.85);backdrop-filter:blur(8px);';
        document.body.appendChild(modal);
    }
    modal.innerHTML = `
        <div style="background:linear-gradient(135deg,#0d1b2a,#0f2236);border:1px solid rgba(116,198,157,.2);
                    border-radius:20px;padding:36px 32px;max-width:360px;width:100%;text-align:center;
                    box-shadow:0 32px 80px rgba(0,0,0,.5);animation:mkModalIn .25s ease;">
            <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;
                         background:${iconColor}18;border:2px solid ${iconColor}44;
                         display:flex;align-items:center;justify-content:center;">
                <i class="${icon}" style="font-size:1.3rem;color:${iconColor};"></i>
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;
                        color:#fff;margin-bottom:8px;">${title}</div>
            <div style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:20px;">
                ${message}
            </div>
            <button id="mkAlertOk" style="background:linear-gradient(135deg,#40916c,#74c69d);
                color:#fff;border:none;border-radius:50px;padding:11px 32px;font-size:.85rem;
                font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:transform .2s;"
                onmouseover="this.style.transform='translateY(-2px)'"
                onmouseout="this.style.transform='none'">OK</button>
        </div>
        <style>@keyframes mkModalIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    const close = () => { modal.style.display = 'none'; document.body.style.overflow = ''; };
    document.getElementById('mkAlertOk').onclick = close;
    modal.onclick = (e) => { if (e.target === modal) close(); };
}

// Set global login state for templates
window.__mkLoggedIn = false;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
async function initializeApp() {
    console.log('initializeApp called');
    
    initializeEventListeners();
    hideAllSections();
    
    // Check if user is already authenticated
    await checkAuthStatus();
    
    console.log('initializeApp completed');
}

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Authentication form listeners
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    if (signupForm) {
        signupForm.addEventListener('submit', handleSignup);
    }

    // Main app listeners (only add if elements exist)
    if (uploadArea) {
        // Upload area click event
        uploadArea.addEventListener('click', () => {
            if (!isUploading) {
                imageInput.click();
            }
        });

        // Drag and drop events
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('dragleave', handleDragLeave);
        uploadArea.addEventListener('drop', handleDrop);
    }

    if (imageInput) {
        // File input change event
        imageInput.addEventListener('change', handleFileSelect);
    }

    if (identifyBtn) {
        // Button events
        identifyBtn.addEventListener('click', identifyBird);
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', resetUpload);
    }

    // Prevent default drag behaviors on document
    document.addEventListener('dragover', (e) => e.preventDefault());
    document.addEventListener('drop', (e) => e.preventDefault());
}

// =========================
// AUTHENTICATION FUNCTIONS
// =========================

/**
 * Check authentication status
 */
async function checkAuthStatus() {
    try {
        const response = await fetch('auth.php?action=check');
        const data = await response.json();
        
        if (data.success && data.authenticated) {
            currentUser = data.user;
            window.__mkLoggedIn = true;
            updateNavigationForLoggedInUser();
        } else {
            currentUser = null;
            window.__mkLoggedIn = false;
            updateNavigationForGuestUser();
        }
    } catch (error) {
        console.error('Error checking auth status:', error);
        currentUser = null;
        updateNavigationForGuestUser();
    }
}

/**
 * Handle login form submission
 */
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!email || !password) {
        showLoginError('Please enter both email and password');
        return;
    }
    
    showLoadingOverlay();
    hideLoginError();
    
    try {
        const response = await fetch('auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            window.__mkLoggedIn = true;
            loginForm.reset();
            // If admin — redirect straight to admin panel
            if (data.is_admin) {
                window.location.href = 'admin.php';
                return;
            }
            // Normal user — stay on page
            updateNavigationForLoggedInUser();
            hideAuthModal();
        } else {
            showLoginError(data.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        showLoginError('Network error. Please try again.');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * Handle signup form submission
 */
async function handleSignup(event) {
    event.preventDefault();
    
    const email = document.getElementById('signupEmail').value.trim();
    const phone = document.getElementById('signupPhone').value.trim();
    const password = document.getElementById('signupPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Client-side validation
    if (!email || !phone || !password || !confirmPassword) {
        showSignupError('Please fill in all fields');
        return;
    }
    
    if (password !== confirmPassword) {
        showSignupError('Passwords do not match');
        return;
    }
    
    if (password.length < 6) {
        showSignupError('Password must be at least 6 characters long');
        return;
    }
    
    showLoadingOverlay();
    hideSignupError();
    
    try {
        const response = await fetch('auth.php?action=signup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                phone: phone,
                password: password,
                confirmPassword: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Registration successful, show login form
            signupForm.reset();
            showLoginForm();
            showSuccessMessage('Account created successfully! Please login with your new credentials.');
        } else {
            showSignupError(data.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Signup error:', error);
        showSignupError('Network error. Please try again.');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * Handle logout
 */
async function handleLogout() {
    // Show beautiful confirm modal instead of browser confirm
    showMKConfirm(
        'fas fa-sign-out-alt',
        '#f87171',
        'Logout',
        'Do you want to logout or explore more birds?',
        'Yes, Logout',
        'Explore More Birds',
        async () => {
            try {
                showLoadingOverlay();
                const response = await fetch('auth.php?action=logout', { method: 'POST' });
                const data = await response.json();
                if (data.success) {
                    currentUser = null;
                    updateNavigationForGuestUser();
                    hideAuthModal();
                    resetUpload();
                }
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                hideLoadingOverlay();
            }
        }
    );
}

// =========================
// UI DISPLAY FUNCTIONS
// =========================

/**
 * Show authentication modal
 */
function showAuthModal() {
    if (authSection) {
        authSection.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

/**
 * Hide authentication modal
 */
function hideAuthModal() {
    if (authSection) {
        authSection.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

/**
 * Show main application (always visible for public access)
 */
function showMainAppPublic() {
    console.log('showMainAppPublic called');
    
    // Explicitly hide auth section with force
    const authEl = document.getElementById('auth-section');
    if (authEl) {
        authEl.style.setProperty('display', 'none', 'important');
        authEl.style.setProperty('visibility', 'hidden', 'important');
        authEl.style.setProperty('opacity', '0', 'important');
        authEl.style.setProperty('pointer-events', 'none', 'important');
        console.log('Auth section forcefully hidden');
    }
    
    // Explicitly show main app
    const mainAppEl = document.getElementById('main-app');
    if (mainAppEl) {
        mainAppEl.style.display = 'block';
        console.log('Main app shown');
    }
    
    // Hide upload sections initially
    hideAllSections();
    
    // Ensure body scrolling is enabled
    document.body.style.overflow = 'auto';
    
    // Remove any modal backdrop
    document.body.classList.remove('modal-open');
}

/**
 * Show main application after login
 */
function showMainApp() {
    hideAuthModal();
    if (mainApp) mainApp.style.display = 'block';
    hideAllSections();
    window.scrollTo(0, 0);
}

/**
 * Show welcome card
 */
function showWelcome() {
    if (welcomeCard) welcomeCard.style.display = 'block';
    if (loginCard) loginCard.style.display = 'none';
    if (signupCard) signupCard.style.display = 'none';
    hideLoginError();
    hideSignupError();
}

/**
 * Show login modal
 */
function showLoginModal() {
    showAuthModal();
    showLoginForm();
}

/**
 * Show signup modal
 */
function showSignupModal() {
    showAuthModal();
    showSignupForm();
}

/**
 * Show login form
 */
function showLoginForm() {
    showAuthModal();
    if (welcomeCard) welcomeCard.style.display = 'none';
    if (loginCard) loginCard.style.display = 'block';
    if (signupCard) signupCard.style.display = 'none';
    hideSignupError();
    
    // Focus on email field
    setTimeout(() => {
        const emailField = document.getElementById('loginEmail');
        if (emailField) emailField.focus();
    }, 100);
}

/**
 * Show signup form
 */
function showSignupForm() {
    showAuthModal();
    if (welcomeCard) welcomeCard.style.display = 'none';
    if (loginCard) loginCard.style.display = 'none';
    if (signupCard) signupCard.style.display = 'block';
    hideLoginError();
    
    // Focus on email field
    setTimeout(() => {
        const emailField = document.getElementById('signupEmail');
        if (emailField) emailField.focus();
    }, 100);
}

/**
 * Update navigation for logged in user
 */
function updateNavigationForLoggedInUser() {
    const guestNavItems = document.querySelectorAll('.guest-nav');
    const userNavItems  = document.querySelectorAll('.user-nav');

    guestNavItems.forEach(item => item.style.display = 'none');
    userNavItems.forEach(item  => item.style.display = 'block');

    // Show full name (First + Last), fallback to email
    const nameSpan = document.getElementById('userDisplayName');
    if (nameSpan && currentUser) {
        const full = [currentUser.first_name, currentUser.last_name].filter(Boolean).join(' ');
        nameSpan.textContent = full || currentUser.email || '';
    }
    // Admin → name link goes to admin dashboard
    const navLink = document.getElementById('userNavLink');
    if (navLink && currentUser && currentUser.role === 'admin') {
        navLink.href = 'admin.php';
    }
}

/**
 * Update navigation for guest user
 */
function updateNavigationForGuestUser() {
    const guestNavItems = document.querySelectorAll('.guest-nav');
    const userNavItems = document.querySelectorAll('.user-nav');
    
    guestNavItems.forEach(item => item.style.display = 'block');
    userNavItems.forEach(item => item.style.display = 'none');
}

/**
 * Show/hide loading overlay
 */
function showLoadingOverlay() {
    if (loadingOverlay) loadingOverlay.style.display = 'flex';
}

function hideLoadingOverlay() {
    if (loadingOverlay) loadingOverlay.style.display = 'none';
}

/**
 * Show/hide error messages
 */
function showLoginError(message) {
    if (loginError) {
        loginError.textContent = message;
        loginError.style.display = 'block';
    }
}

function hideLoginError() {
    if (loginError) loginError.style.display = 'none';
}

function showSignupError(message) {
    if (signupError) {
        signupError.textContent = message;
        signupError.style.display = 'block';
    }
}

function hideSignupError() {
    if (signupError) signupError.style.display = 'none';
}

function showSuccessMessage(message) {
    // Show success in login error div with success styling
    if (loginError) {
        loginError.textContent = message;
        loginError.className = 'alert alert-success mt-3';
        loginError.style.display = 'block';
        
        // Reset to error styling after 5 seconds
        setTimeout(() => {
            loginError.className = 'alert alert-danger mt-3';
            hideLoginError();
        }, 5000);
    }
}

// =========================
// BIRD IDENTIFICATION FUNCTIONS (Original functionality)
// =========================

/**
 * Hide all result sections
 */
function hideAllSections() {
    if (previewSection) previewSection.style.display = 'none';
    if (loadingSection) loadingSection.style.display = 'none';
    if (resultsSection) resultsSection.style.display = 'none';
    if (errorSection) errorSection.style.display = 'none';
}

/**
 * Handle file selection from input
 */
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        validateAndPreviewFile(file);
    }
}

/**
 * Handle drag over event
 */
function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    if (uploadArea) uploadArea.classList.add('dragover');
}

/**
 * Handle drag leave event
 */
function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    if (uploadArea) uploadArea.classList.remove('dragover');
}

/**
 * Handle drop event
 */
function handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    if (uploadArea) uploadArea.classList.remove('dragover');

    const files = event.dataTransfer.files;
    if (files.length > 0) {
        validateAndPreviewFile(files[0]);
    }
}

/**
 * Validate file and show preview
 */
function validateAndPreviewFile(file) {
    // Allow preview without login — login modal shows only when submitting unknown bird
    // Reset previous states
    hideAllSections();
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showError('Please select a valid image file (JPG, PNG, WEBP).');
        return;
    }

    // Validate file size (5MB limit)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        showError('File size must be less than 5MB.');
        return;
    }

    // Store selected file
    selectedFile = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = function(e) {
        if (previewImage) {
            previewImage.src = e.target.result;
            previewImage.alt = file.name;
        }
        if (previewSection) previewSection.style.display = 'block';
    };
    reader.readAsDataURL(file);
}
        if (!window._locationAsked) {
            window._locationAsked = true;
            navigator.geolocation?.getCurrentPosition(pos => {
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
            .then(r=>r.json()).then(d=>{ window._userLocation = (d.address?.city||d.address?.town||d.address?.village||'') + (d.address?.country ? ', '+d.address.country : ''); });
    });
}          
/**
 * Show login required alert
 */
function showLoginRequiredAlert() {
    // Use beautiful modal if available (index.html), else redirect
    if (typeof showLoginRequiredModal === 'function') {
        showLoginRequiredModal(
            '🔒 Login Required',
            'You need to be logged in to upload images and identify bird species.'
        );
    } else {
        window.location.href = 'login.html';
    }
}

/**
 * Identify bird species
 */
async function identifyBird() {
    if (!selectedFile) {
        showError('Please select an image first.');
        return;
    }

    // Show loading state
    hideAllSections();
    if (loadingSection) loadingSection.style.display = 'block';
    isUploading = true;

    // After 15 seconds, update loading message to warn about first-run download
    const downloadNoticeTimer = setTimeout(() => {
        const mainText = document.getElementById('loadingMainText');
        const subText  = document.getElementById('loadingSubText');
        if (mainText) mainText.textContent = 'Still working... AI is processing deeply.';
        if (subText)  subText.textContent  = 'Complex images take a little longer — almost there!';
    }, 15000);

    // 3-minute timeout — needed for first-time HuggingFace model download
    const controller = new AbortController();
    const fetchTimeout = setTimeout(() => controller.abort(), 180000); // 3 minutes

    const formData = new FormData();
    formData.append('image', selectedFile);
    if (window._userLocation) formData.append('location', window._userLocation);

    try {
        const response = await fetch('identify.php', {
            method: 'POST',
            body: formData,
            signal: controller.signal
        });

        clearTimeout(fetchTimeout);
        clearTimeout(downloadNoticeTimer);

        // Always read JSON body — even on 400 (e.g. NOT_A_BIRD error)
        const data = await response.json();

        if (data.success) {
            displayResults(data);
        } else if (data.error_code === 'NOT_A_BIRD') {
            showNotABirdError(data.message);
        } else {
            showError(data.message || 'Identification failed. Please try again.');
        }
    } catch (error) {
        clearTimeout(fetchTimeout);
        clearTimeout(downloadNoticeTimer);
        console.error('Identification error:', error);
        if (error.name === 'AbortError') {
            showError('This is taking longer than expected. Please try again in a moment.');
        } else {
            showError('Network error occurred. Please check your connection and try again.');
        }
    } finally {
        hideLoadingState();
        isUploading = false;
    }
}

/**
 * Display identification results
 */
function displayResults(data) {
    if (!resultsContent) return;

    // ── UNKNOWN BIRD ─────────────────────────────────────────
    if (data.unknown) {
        const imgUrl = data.image_url || '';
        resultsContent.innerHTML = `
            <div style="text-align:center;padding:10px 0 18px;">
                ${imgUrl ? `<img src="${imgUrl}" alt="uploaded"
                    style="width:80px;height:80px;object-fit:cover;border-radius:14px;
                           border:2px solid rgba(248,113,113,.35);margin-bottom:14px;">` : ''}
                <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;
                             background:rgba(248,113,113,.12);border:2px solid rgba(248,113,113,.3);
                             display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-question" style="font-size:1.4rem;color:#f87171;"></i>
                </div>
                <div style="font-family:'Playfair Display',serif;font-size:1.2rem;
                            font-weight:700;color:#f87171;margin-bottom:8px;">Unknown Bird</div>
                <div style="font-size:.83rem;color:rgba(255,255,255,.5);margin-bottom:20px;line-height:1.6;">
                    Our AI couldn't identify this species with enough confidence.<br>
                    Help us improve — submit it for admin review!
                </div>
            </div>

            <!-- Submission Box: login prompt if guest, form if logged in -->
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(116,198,157,.2);
                        border-radius:16px;padding:18px;" id="unknownSubmitBox">
                ${!window.__mkLoggedIn
                  ? `<div style="text-align:center;padding:8px 0 4px;">
                        <div style="width:48px;height:48px;border-radius:50%;margin:0 auto 12px;
                                    background:rgba(116,198,157,.15);border:1.5px solid rgba(116,198,157,.3);
                                    display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-lock" style="color:#74c69d;font-size:1.1rem;"></i>
                        </div>
                        <div style="font-size:.88rem;font-weight:700;color:#fff;margin-bottom:6px;">
                            Login to Submit for Review
                        </div>
                        <div style="font-size:.78rem;color:rgba(255,255,255,.45);margin-bottom:16px;line-height:1.5;">
                            Help us improve MKfinder by submitting this unknown bird.<br>Login or create a free account to continue.
                        </div>
                        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                            <a href="login.html" style="flex:1;min-width:110px;max-width:160px;
                                background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;
                                border-radius:50px;padding:10px 18px;font-size:.83rem;font-weight:700;
                                text-decoration:none;display:inline-flex;align-items:center;
                                justify-content:center;gap:7px;font-family:'Inter',sans-serif;transition:transform .2s;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='none'">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="signup.html" style="flex:1;min-width:110px;max-width:160px;
                                background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);
                                border:1px solid rgba(255,255,255,.15);border-radius:50px;
                                padding:10px 18px;font-size:.83rem;font-weight:600;
                                text-decoration:none;display:inline-flex;align-items:center;
                                justify-content:center;gap:7px;font-family:'Inter',sans-serif;transition:transform .2s;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='none'">
                                <i class="fas fa-user-plus"></i> Sign Up Free
                            </a>
                        </div>
                        <button onclick="resetUpload()" style="margin-top:12px;background:none;
                            border:none;color:rgba(255,255,255,.3);font-size:.75rem;cursor:pointer;
                            font-family:'Inter',sans-serif;">
                            <i class="fas fa-redo me-1"></i> Try another photo
                        </button>
                    </div>`
                  : `<div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                color:#74c69d;margin-bottom:14px;">
                        <i class="fas fa-paper-plane me-1"></i> Submit to Admin for Review
                    </div>
                    <input id="ub_species" placeholder="What bird do you think this is? (optional)"
                        style="width:100%;background:#fff;border:1.5px solid rgba(116,198,157,.4);
                               border-radius:10px;padding:10px 14px;color:#1a1a1a;font-size:.85rem;
                               font-family:'Inter',sans-serif;outline:none;margin-bottom:10px;" />
                    <textarea id="ub_note" rows="2" placeholder="Any extra details for the admin? (optional)"
                        style="width:100%;background:#fff;border:1.5px solid rgba(116,198,157,.4);
                               border-radius:10px;padding:10px 14px;color:#1a1a1a;font-size:.85rem;
                               font-family:'Inter',sans-serif;outline:none;resize:none;margin-bottom:14px;"></textarea>
                    <div id="ub_msg" style="display:none;font-size:.82rem;padding:8px 12px;
                                             border-radius:8px;margin-bottom:10px;"></div>
                    <div style="display:flex;gap:10px;">
                        <button onclick="submitUnknownBird('${data.identification_id}','${data.image_hash}')"
                            style="flex:1;background:linear-gradient(135deg,#40916c,#74c69d);
                                   color:#fff;border:none;border-radius:50px;padding:10px 18px;
                                   font-size:.83rem;font-weight:700;cursor:pointer;
                                   font-family:'Inter',sans-serif;transition:transform .2s;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='none'">
                            <i class="fas fa-paper-plane me-1"></i> Submit Request
                        </button>
                        <button onclick="resetUpload()"
                            style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);
                                   border:1px solid rgba(255,255,255,.15);border-radius:50px;
                                   padding:10px 16px;font-size:.83rem;cursor:pointer;
                                   font-family:'Inter',sans-serif;transition:transform .2s;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='none'">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>`}
            </div>
        `;
        if (resultsSection) resultsSection.style.display = 'block';
        return;
    }

    // ── KNOWN BIRD ───────────────────────────────────────────
    const species    = data.species_info || data;
    const confidence = data.confidence || 95;
    const imgUrl     = data.image_url || '';
    const mode       = data.mode || '';

    const statusColors = {
        'Least Concern':        '#74c69d',
        'Near Threatened':      '#facc15',
        'Vulnerable':           '#fb923c',
        'Endangered':           '#f87171',
        'Critically Endangered':'#dc2626',
    };
    const statusColor = statusColors[species.conservation_status] || '#9ca3af';
    const barColor = confidence >= 80 ? '#74c69d' : confidence >= 60 ? '#facc15' : '#f87171';

    const chars = Array.isArray(species.characteristics) ? species.characteristics : [];
    const charsHTML = chars.length ? `
        <div style="margin-top:14px;">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                        color:rgba(255,255,255,.4);margin-bottom:8px;">Key Characteristics</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                ${chars.map(c => `
                    <span style="background:rgba(116,198,157,.12);border:1px solid rgba(116,198,157,.25);
                                 color:rgba(255,255,255,.8);font-size:.75rem;padding:3px 10px;border-radius:50px;">
                        <i class="fas fa-check" style="color:#74c69d;margin-right:4px;font-size:.65rem;"></i>${c}
                    </span>`).join('')}
            </div>
        </div>` : '';

    const infoRows = [
        { icon: 'fa-tree',           label: 'Habitat',  val: species.habitat },
        { icon: 'fa-drumstick-bite', label: 'Diet',     val: species.diet },
        { icon: 'fa-feather-alt',    label: 'Behavior', val: species.behavior },
    ].filter(r => r.val).map(r => `
        <div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.06);">
            <div style="width:28px;height:28px;border-radius:8px;background:rgba(116,198,157,.15);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas ${r.icon}" style="color:#74c69d;font-size:.75rem;"></i>
            </div>
            <div>
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                            color:rgba(255,255,255,.35);">${r.label}</div>
                <div style="font-size:.82rem;color:rgba(255,255,255,.8);margin-top:1px;">${r.val}</div>
            </div>
        </div>`).join('');

    resultsContent.innerHTML = `
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
            ${imgUrl ? `<img src="${imgUrl}" alt="uploaded bird"
                style="width:62px;height:62px;object-fit:cover;border-radius:12px;
                       border:2px solid rgba(116,198,157,.35);flex-shrink:0;">` : ''}
            <div style="flex:1;min-width:0;">
                <div style="font-family:'Playfair Display',serif;font-size:1.25rem;font-weight:700;
                            color:#74c69d;line-height:1.2;">${species.name}</div>
                <div style="font-size:.8rem;color:rgba(255,255,255,.45);font-style:italic;">
                    ${species.scientific_name || ''}</div>
            </div>
            <span style="background:${statusColor}22;color:${statusColor};border:1px solid ${statusColor}44;
                         font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:50px;
                         white-space:nowrap;flex-shrink:0;">
                <i class="fas fa-shield-alt" style="margin-right:3px;font-size:.6rem;"></i>
                ${species.conservation_status || 'Unknown'}
            </span>
        </div>

        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                             color:rgba(255,255,255,.4);">AI Confidence</span>
                <span style="font-size:.9rem;font-weight:800;color:${barColor};">${confidence}%</span>
            </div>
            <div style="height:6px;background:rgba(255,255,255,.1);border-radius:50px;overflow:hidden;">
                <div style="height:100%;width:${confidence}%;background:${barColor};
                            border-radius:50px;transition:width .8s ease;"></div>
            </div>
            ${mode === 'ai_model' ? `<div style="font-size:.68rem;color:rgba(116,198,157,.6);margin-top:4px;">
                <i class="fas fa-brain" style="margin-right:3px;"></i>Real AI Model</div>` :
              mode === 'override' ? `<div style="font-size:.68rem;color:rgba(116,198,157,.6);margin-top:4px;">
                <i class="fas fa-check-circle" style="margin-right:3px;"></i>AI Trained</div>` :
              mode === 'demo' ? `<div style="font-size:.68rem;color:rgba(255,204,0,.5);margin-top:4px;">
                <i class="fas fa-flask" style="margin-right:3px;"></i>Demo Mode</div>` : ''}
        </div>

        ${species.description ? `
        <div style="font-size:.83rem;color:rgba(255,255,255,.65);line-height:1.6;
                    padding:10px 12px;background:rgba(255,255,255,.04);border-radius:10px;
                    border-left:3px solid rgba(116,198,157,.4);margin-bottom:14px;">
            ${species.description}
        </div>` : ''}

        ${infoRows}
        ${charsHTML}

        <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
            <button onclick="resetUpload()" style="flex:1;min-width:120px;
                background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;border:none;
                border-radius:50px;padding:10px 18px;font-size:.83rem;font-weight:700;
                cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;
                font-family:'Inter',sans-serif;transition:transform .2s;"
                onmouseover="this.style.transform='translateY(-2px)'"
                onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-redo"></i> Identify Another
            </button>
            <a href="species.php?species=${encodeURIComponent(species.name)}"
                style="flex:1;min-width:120px;background:rgba(255,255,255,.08);
                color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.18);
                border-radius:50px;padding:10px 18px;font-size:.83rem;font-weight:600;
                text-decoration:none;display:flex;align-items:center;justify-content:center;gap:7px;
                transition:transform .2s;"
                onmouseover="this.style.transform='translateY(-2px)'"
                onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-binoculars"></i> Full Profile
            </a>
        </div>
    `;

    if (resultsSection) resultsSection.style.display = 'block';
}

/**
 * Submit unknown bird to admin
 */
async function submitUnknownBird(imgUrl) {
    const birdName  = (document.getElementById('ub_name')?.value    || '').trim();
    const desc      = (document.getElementById('ub_desc')?.value    || '').trim();
    const habitat   = (document.getElementById('ub_habitat')?.value || '').trim();
    const msgEl     = document.getElementById('ub_msg');

    const showMsg = (text, isErr) => {
        if (!msgEl) return;
        msgEl.textContent = text;
        msgEl.style.display = 'block';
        msgEl.style.background  = isErr ? 'rgba(248,113,113,.12)' : 'rgba(116,198,157,.12)';
        msgEl.style.color       = isErr ? '#f87171' : '#74c69d';
        msgEl.style.border      = isErr ? '1px solid rgba(248,113,113,.25)' : '1px solid rgba(116,198,157,.25)';
    };

    try {
        // Build a FormData from the already-uploaded image path
        const fd = new FormData();
        fd.append('bird_name',   birdName  || 'Unknown Bird');
        fd.append('description', desc);
        fd.append('habitat',     habitat);
        // Re-attach the file if still in memory
        if (selectedFile) fd.append('image', selectedFile);

        const res  = await fetch('add_species.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showMsg('✓ Submitted! Our admin will review your bird.', false);
            document.getElementById('unknownSubmitBox').style.opacity = '0.6';
            document.getElementById('unknownSubmitBox').style.pointerEvents = 'none';
        } else {
            showMsg(data.message || 'Submission failed. Please try again.', true);
        }
    } catch(e) {
        showMsg('Network error. Please check XAMPP is running.', true);
    }
}

/**
 * Show error message
 */
function showError(message) {
    hideAllSections();
    if (errorContent) errorContent.textContent = message;
    if (errorSection) errorSection.style.display = 'block';
}

/**
 * Show "not a bird photo" beautiful card
 */
function showNotABirdError(customMsg) {
    hideAllSections();
    if (!resultsContent) return;

    const msg = customMsg || "Our AI detected that this image doesn't contain a bird. Please upload a clear photo of a bird.";

    resultsContent.innerHTML = `
        <div style="text-align:center;padding:16px 8px;">
            <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;
                         background:rgba(251,146,60,.12);border:2px solid rgba(251,146,60,.3);
                         display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-ban" style="font-size:1.6rem;color:#fb923c;"></i>
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:1.15rem;
                        font-weight:700;color:#fb923c;margin-bottom:10px;">
                Not a Bird Photo
            </div>
            <div style="font-size:.85rem;color:rgba(255,255,255,.55);line-height:1.7;
                        margin-bottom:20px;max-width:300px;margin-left:auto;margin-right:auto;">
                ${msg}
            </div>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                <button onclick="resetUpload()"
                    style="background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;
                           border:none;border-radius:50px;padding:10px 22px;font-size:.83rem;
                           font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;
                           transition:transform .2s;"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='none'">
                    <i class="fas fa-redo me-1"></i> Try Another Photo
                </button>
                <a href="species.php"
                    style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);
                           border:1px solid rgba(255,255,255,.18);border-radius:50px;
                           padding:10px 22px;font-size:.83rem;font-weight:600;
                           text-decoration:none;display:inline-flex;align-items:center;gap:7px;
                           transition:transform .2s;"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='none'">
                    <i class="fas fa-feather"></i> View Species
                </a>
            </div>
        </div>
    `;
    if (resultsSection) resultsSection.style.display = 'block';
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    if (loadingSection) loadingSection.style.display = 'none';
}

/**
 * Reset upload form
 */
function resetUpload() {
    selectedFile = null;
    isUploading = false;
    
    if (imageInput) imageInput.value = '';
    if (previewImage) {
        previewImage.src = '';
        previewImage.alt = '';
    }
    
    hideAllSections();
    
    if (uploadArea) uploadArea.classList.remove('dragover');
}

/**
 * Scroll to upload section
 */
function scrollToUpload() {
    const uploadSection = document.getElementById('upload-section');
    if (uploadSection) {
        uploadSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Debounce function for performance optimization
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Export functions for potential use in other scripts
window.MKfinder = {
    scrollToUpload,
    resetUpload,
    formatFileSize,
    showLoadingOverlay,
    hideLoadingOverlay,
    debounce,
    showWelcome,
    showLoginForm,
    showSignupForm,
    showLoginModal,
    showSignupModal,
    handleLogout,
    showMainApp,
    showMainAppPublic
};