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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
async function initializeApp() {
    initializeEventListeners();
    hideAllSections();
    
    // Check if user is already authenticated
    await checkAuthStatus();
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
            showMainApp();
            updateNavigationForLoggedInUser();
        } else {
            currentUser = null;
            showAuthSection();
            updateNavigationForGuestUser();
        }
    } catch (error) {
        console.error('Error checking auth status:', error);
        showAuthSection();
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
            showMainApp();
            updateNavigationForLoggedInUser();
            loginForm.reset();
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
            showLoginForm();
            showSuccessMessage('Account created successfully! Please login with your new credentials.');
            signupForm.reset();
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
    try {
        showLoadingOverlay();
        
        const response = await fetch('auth.php?action=logout', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = null;
            showAuthSection();
            updateNavigationForGuestUser();
            resetUpload();
        }
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        hideLoadingOverlay();
    }
}

// =========================
// UI DISPLAY FUNCTIONS
// =========================

/**
 * Show authentication section
 */
function showAuthSection() {
    document.body.classList.add('auth-active');
    document.body.classList.remove('app-active');
    if (authSection) authSection.style.display = 'flex';
    if (mainApp) mainApp.style.display = 'none';
    showWelcome();
}

/**
 * Show main application
 */
function showMainApp() {
    document.body.classList.add('app-active');
    document.body.classList.remove('auth-active');
    if (authSection) authSection.style.display = 'none';
    if (mainApp) mainApp.style.display = 'block';
    hideAllSections();
    
    // Scroll to top after showing main app
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
 * Show login form
 */
function showLoginForm() {
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
    const authNavItems = document.querySelectorAll('.auth-nav');
    const appNavItems = document.querySelectorAll('.app-nav');
    
    authNavItems.forEach(item => item.style.display = 'none');
    appNavItems.forEach(item => item.style.display = 'block');
    
    if (userEmail && currentUser) {
        userEmail.textContent = currentUser.email;
    }
}

/**
 * Update navigation for guest user
 */
function updateNavigationForGuestUser() {
    const authNavItems = document.querySelectorAll('.auth-nav');
    const appNavItems = document.querySelectorAll('.app-nav');
    
    authNavItems.forEach(item => item.style.display = 'block');
    appNavItems.forEach(item => item.style.display = 'none');
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

    // Prepare form data
    const formData = new FormData();
    formData.append('image', selectedFile);

    try {
        const response = await fetch('identify.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            displayResults(data);
        } else {
            showError(data.message || 'Identification failed. Please try again.');
        }
    } catch (error) {
        console.error('Identification error:', error);
        showError('Network error occurred. Please check your connection and try again.');
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
    
    const species = data.species_info;
    const confidence = data.confidence || 95;
    
    // Create results HTML
    const resultsHTML = `
        <div class="row">
            <div class="col-md-4 text-center mb-3">
                <img src="images/${species.name.toLowerCase().replace(/\s+/g, '-')}.svg" 
                     alt="${species.name}" 
                     class="img-fluid" 
                     style="max-height: 200px;">
            </div>
            <div class="col-md-8">
                <h4 class="text-primary">${species.name}</h4>
                <p class="text-muted"><em>${species.scientific_name}</em></p>
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <strong class="me-2">Confidence:</strong>
                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                            <div class="progress-bar bg-success" 
                                 style="width: ${confidence}%"
                                 aria-valuenow="${confidence}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <span class="badge bg-success">${confidence}%</span>
                    </div>
                </div>
                <p><strong>Description:</strong> ${species.description}</p>
                <p><strong>Habitat:</strong> ${species.habitat}</p>
                <p><strong>Diet:</strong> ${species.diet}</p>
                <p><strong>Behavior:</strong> ${species.behavior}</p>
                <p><strong>Conservation Status:</strong> 
                   <span class="badge bg-info">${species.conservation_status}</span>
                </p>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Key Characteristics:</h5>
            <ul class="list-unstyled">
                ${species.characteristics.map(char => `<li><i class="fas fa-check text-success me-2"></i>${char}</li>`).join('')}
            </ul>
        </div>
        
        <div class="mt-4 text-center">
            <button class="btn btn-primary me-2" onclick="resetUpload()">
                <i class="fas fa-plus me-2"></i>
                Identify Another Bird
            </button>
            <a href="species.php" class="btn btn-outline-primary">
                <i class="fas fa-info-circle me-2"></i>
                Learn More About Birds
            </a>
        </div>
    `;
    
    resultsContent.innerHTML = resultsHTML;
    if (resultsSection) resultsSection.style.display = 'block';
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
    handleLogout,
    showMainApp
};