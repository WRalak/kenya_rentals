// KenyaRentals Application JavaScript

class KenyaRentalsApp {
    constructor() {
        this.init();
    }

    init() {
        this.initDatePickers();
        this.initSearchFilters();
        this.initBookingModal();
        this.initFormValidations();
    }

    initDatePickers() {
        // Initialize date pickers for booking forms
        const startDateInputs = document.querySelectorAll('input[name="start_date"]');
        const endDateInputs = document.querySelectorAll('input[name="end_date"]');

        startDateInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const endDate = e.target.closest('form').querySelector('input[name="end_date"]');
                if (endDate) {
                    endDate.min = e.target.value;
                    if (endDate.value && endDate.value < e.target.value) {
                        endDate.value = '';
                    }
                }
            });
        });
    }

    initSearchFilters() {
        // Real-time search filter updates
        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('input', this.debounce(() => {
                this.performSearch();
            }, 500));
        }
    }

    initBookingModal() {
        // Handle booking modal interactions
        const bookingModal = document.getElementById('bookingModal');
        if (bookingModal) {
            bookingModal.addEventListener('click', (e) => {
                if (e.target === bookingModal) {
                    this.closeBookingModal();
                }
            });
        }
    }

    initFormValidations() {
        // Add custom form validations
        const forms = document.querySelectorAll('form[needs-validation]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showFormErrors(form);
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                this.markInvalid(input, 'This field is required');
            } else {
                this.markValid(input);
            }
        });

        return isValid;
    }

    markInvalid(input, message) {
        input.classList.add('border-red-500');
        input.classList.remove('border-gray-300');

        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('p');
            errorElement.className = 'error-message text-red-500 text-sm mt-1';
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    markValid(input) {
        input.classList.remove('border-red-500');
        input.classList.add('border-gray-300');

        const errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
        }
    }

    showFormErrors(form) {
        const firstInvalid = form.querySelector('.border-red-500');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
    }

    async performSearch() {
        const form = document.getElementById('search-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch(`/api/properties.php?${params}`);
            const properties = await response.json();

            this.updateSearchResults(properties);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    updateSearchResults(properties) {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;

        if (properties.length === 0) {
            resultsContainer.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">No properties found</h3>
                    <p class="text-gray-500 mt-2">Try adjusting your search filters</p>
                </div>
            `;
            return;
        }

        resultsContainer.innerHTML = properties.map(property => `
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300 property-card">
                <div class="h-48 bg-gray-200 relative">
                    <img src="/assets/images/property-placeholder.jpg" alt="${property.title}" class="w-full h-full object-cover">
                    <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary price-tag">
                        KSh ${this.formatPrice(property.price_per_day)}/day
                    </span>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">${property.title}</h3>
                    <p class="text-gray-600 mb-4">${property.description.substring(0, 100)}...</p>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            ${property.location}
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-user mr-2"></i>
                            ${property.landlord_name}
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-arrows-alt mr-2"></i>
                            ${property.size_sqft || 'N/A'} sqft
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                            ${property.type}
                        </span>
                        <button onclick="openBookingModal(${property.id})" 
                                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    formatPrice(price) {
        return new Intl.NumberFormat().format(price);
    }

    debounce(func, wait) {
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

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Global functions for modal handling
    openBookingModal(propertyId) {
        const modal = document.getElementById('bookingModal');
        const propertyIdInput = document.getElementById('booking_property_id');
        
        if (propertyIdInput) {
            propertyIdInput.value = propertyId;
        }
        
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    closeBookingModal() {
        const modal = document.getElementById('bookingModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.kenyaRentalsApp = new KenyaRentalsApp();
});

// Handle booking form submission
document.addEventListener('DOMContentLoaded', () => {
    const bookingForms = document.querySelectorAll('form[action="/api/bookings.php"]');
    
    bookingForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const response = await fetch('/api/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.kenyaRentalsApp.showNotification(result.message, 'success');
                    window.kenyaRentalsApp.closeBookingModal();
                    
                    // Redirect to bookings page after successful booking
                    setTimeout(() => {
                        window.location.href = '/dashboard/tenant/bookings.php';
                    }, 2000);
                } else {
                    window.kenyaRentalsApp.showNotification(result.error, 'error');
                }
            } catch (error) {
                window.kenyaRentalsApp.showNotification('An error occurred. Please try again.', 'error');
            } finally {
                // Restore button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });
});
// KenyaRentals Application JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('KenyaRentals app loaded');
    
    // Initialize date pickers
    const startDateInputs = document.querySelectorAll('input[name="start_date"]');
    const endDateInputs = document.querySelectorAll('input[name="end_date"]');

    startDateInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const endDate = e.target.closest('form').querySelector('input[name="end_date"]');
            if (endDate) {
                endDate.min = e.target.value;
                if (endDate.value && endDate.value < e.target.value) {
                    endDate.value = '';
                }
            }
        });
    });

    // Global modal functions
    window.openBookingModal = function(propertyId) {
        const modal = document.getElementById('bookingModal');
        const propertyIdInput = document.getElementById('booking_property_id');
        
        if (propertyIdInput) {
            propertyIdInput.value = propertyId;
        }
        
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    window.closeBookingModal = function() {
        const modal = document.getElementById('bookingModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };

    // Handle booking form submission
    const bookingForms = document.querySelectorAll('form[action="/kenya_rentals/api/bookings.php"]');
    
    bookingForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const response = await fetch('/kenya_rentals/api/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Booking request submitted successfully!');
                    window.closeBookingModal();
                    
                    // Redirect to bookings page after successful booking
                    setTimeout(() => {
                        window.location.href = '/kenya_rentals/dashboard/tenant/bookings.php';
                    }, 2000);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                // Restore button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });
});