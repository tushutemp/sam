// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('mobileMenuToggle');

        if (window.innerWidth <= 992 &&
            !sidebar.contains(event.target) &&
            !menuToggle.contains(event.target) &&
            sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });

    // Auto remove alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-message').forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        });
    }, 5000);
});

// View opinion details
async function viewOpinion(opinionId) {
    const modalBody = document.getElementById('opinionDetailBody');
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div>Loading opinion details...</div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('opinionDetailModal'));
    modal.show();

    try {
        const response = await fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=fetch_opinion&opinion_id=${opinionId}`
        });

        if (!response.ok) {
            throw new Error('Failed to fetch opinion data');
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load opinion');
        }

        const opinion = result.data;

        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-user me-2"></i>Name</label>
                        <div class="value">${opinion.name}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-envelope me-2"></i>Email</label>
                        <div class="value">${opinion.email}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-phone me-2"></i>Phone</label>
                        <div class="value">${opinion.phone}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-tag me-2"></i>Category</label>
                        <div class="value">${opinion.category}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-calendar me-2"></i>Date Submitted</label>
                        <div class="value">${opinion.submission_date}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-language me-2"></i>Language</label>
                        <div class="value">${opinion.language}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-info-circle me-2"></i>Status</label>
                        <div class="value">${opinion.status}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="opinion-detail">
                        <label><i class="fas fa-id-card me-2"></i>Opinion ID</label>
                        <div class="value">${opinion.id}</div>
                    </div>
                </div>
            </div>
            <div class="opinion-detail">
                <label><i class="far fa-comment-dots me-2"></i>Opinion</label>
                <div class="value" style="min-height: 100px; white-space: pre-wrap; padding: 15px;">${opinion.opinion}</div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading opinion:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> Failed to load opinion details. Please try again.
            </div>
        `;
    }
}
