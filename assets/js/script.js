// ===== NAV TOGGLE =====
function toggleNav() {
    const menu = document.getElementById('navMenu');
    if (menu) {
        menu.classList.toggle('open');
    }
}

// Close nav when clicking outside on mobile
document.addEventListener('click', function(e) {
    const menu = document.getElementById('navMenu');
    const toggle = document.querySelector('.nav-toggle');
    if (menu && toggle && !menu.contains(e.target) && !toggle.contains(e.target)) {
        menu.classList.remove('open');
    }
});

// ===== FORM VALIDATION =====
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhoneNumber(phone) {
    const re = /^[\d\s\-\+\(\)]{10,}$/;
    return re.test(phone);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(value);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function confirmAction(message) {
    return confirm(message);
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--red-400)';
                    field.style.boxShadow = '0 0 0 3px rgba(244,67,54,0.1)';
                } else {
                    field.style.borderColor = '';
                    field.style.boxShadow = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });

    // Stagger card animations
    const cards = document.querySelectorAll('.card, .hero-stat');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 80 + (i * 80));
    });

    // Stagger table row animations
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, i) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';
        setTimeout(() => {
            row.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 200 + (i * 50));
    });
});

// ===== EXPORT CSV =====
function exportTableToCSV(filename) {
    const csv = [];
    const rows = document.querySelectorAll('table tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// ===== PRINT =====
function printPage() {
    window.print();
}

// ===== AUTO-LOGOUT ON BACK BUTTON =====
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.href = '../logout.php';
    }
});
