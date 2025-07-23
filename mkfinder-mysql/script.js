// Global variables
let selectedFile = null;
let isUploading = false;

// DOM elements
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

// Initialize event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    hideAllSections();
});

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Upload area click event
    uploadArea.addEventListener('click', () => {
        if (!isUploading) {
            imageInput.click();
        }
    });

    // File input change event
    imageInput.addEventListener('change', handleFileSelect);

    // Drag and drop events
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleDrop);

    // Button events
    identifyBtn.addEventListener('click', identifyBird);
    resetBtn.addEventListener('click', resetUpload);

    // Prevent default drag behaviors on document
    document.addEventListener('dragover', (e) => e.preventDefault());
    document.addEventListener('drop', (e) => e.preventDefault());
}

/**
 * Hide all result sections
 */
function hideAllSections() {
    previewSection.style.display = 'none';
    loadingSection.style.display = 'none';
    resultsSection.style.display = 'none';
    errorSection.style.display = 'none';
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
    uploadArea.classList.add('dragover');
}

/**
 * Handle drag leave event
 */
function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    uploadArea.classList.remove('dragover');
}

/**
 * Handle drop event
 */
function handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    uploadArea.classList.remove('dragover');

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

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        previewImage.src = e.target.result;
        previewSection.style.display = 'block';
        previewSection.classList.add('fade-in');
    };
    reader.readAsDataURL(file);
}

/**
 * Identify bird species
 */
async function identifyBird() {
    if (!selectedFile || isUploading) {
        return;
    }

    isUploading = true;
    hideAllSections();
    loadingSection.style.display = 'block';
    loadingSection.classList.add('fade-in');

    const formData = new FormData();
    formData.append('image', selectedFile);

    try {
        const response = await fetch('identify.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showResults(result.data);
        } else {
            showError(result.message || 'Failed to identify bird species. Please try again.');
        }
    } catch (error) {
        console.error('Error identifying bird:', error);
        showError('Network error occurred. Please check your connection and try again.');
    } finally {
        isUploading = false;
        loadingSection.style.display = 'none';
    }
}

/**
 * Show identification results
 */
function showResults(data) {
    const { species, confidence, description, characteristics } = data;
    
    const resultsHTML = `
        <div class="result-item mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">${species}</h6>
                <span class="badge bg-success">${confidence}% confidence</span>
            </div>
            <div class="confidence-bar">
                <div class="confidence-fill" style="width: ${confidence}%"></div>
            </div>
        </div>
        
        ${description ? `
            <div class="mt-3">
                <h6 class="fw-bold">Description:</h6>
                <p class="mb-2">${description}</p>
            </div>
        ` : ''}
        
        ${characteristics && characteristics.length > 0 ? `
            <div class="mt-3">
                <h6 class="fw-bold">Key Characteristics:</h6>
                <ul class="list-unstyled">
                    ${characteristics.map(char => `<li><i class="fas fa-check text-success me-2"></i>${char}</li>`).join('')}
                </ul>
            </div>
        ` : ''}
        
        <div class="mt-3">
            <a href="species.php?species=${encodeURIComponent(species)}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-info-circle me-1"></i>
                Learn More
            </a>
        </div>
    `;

    resultsContent.innerHTML = resultsHTML;
    resultsSection.style.display = 'block';
    resultsSection.classList.add('slide-up');
}

/**
 * Show error message
 */
function showError(message) {
    errorContent.innerHTML = `<p class="mb-0">${message}</p>`;
    errorSection.style.display = 'block';
    errorSection.classList.add('fade-in');
}

/**
 * Reset upload form
 */
function resetUpload() {
    selectedFile = null;
    imageInput.value = '';
    previewImage.src = '';
    uploadArea.classList.remove('dragover');
    hideAllSections();
}

/**
 * Scroll to upload section
 */
function scrollToUpload() {
    document.getElementById('upload-section').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
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
 * Show loading state for any element
 */
function showLoading(element, text = 'Loading...') {
    element.innerHTML = `
        <div class="d-flex align-items-center justify-content-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            ${text}
        </div>
    `;
}

/**
 * Utility function to debounce function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for potential use in other scripts
window.MKfinder = {
    scrollToUpload,
    resetUpload,
    formatFileSize,
    showLoading,
    debounce
};
