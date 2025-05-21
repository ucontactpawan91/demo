document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const role = row.querySelector('select').value.toLowerCase();
                
                if (username.includes(searchValue) || 
                    email.includes(searchValue) || 
                    role.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Role select handling
    document.querySelectorAll('.role-select').forEach(select => {
        select.addEventListener('change', async function() {
            const userId = this.dataset.userId;
            const newRole = this.value;
            
            try {
                const response = await fetch('update_role.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        role: newRole
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    // Reload page to update permissions
                    location.reload();
                } else {
                    alert('Failed to update role: ' + (data.message || 'Unknown error'));
                    this.value = this.getAttribute('data-original-value');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating role');
                this.value = this.getAttribute('data-original-value');
            }
        });

        // Store original value for rollback on error
        select.setAttribute('data-original-value', select.value);
    });

    // Permission switches handling
    document.querySelectorAll('.permission-switch').forEach(switchEl => {
        switchEl.addEventListener('change', async function() {
            const userId = this.dataset.userId;
            const permission = this.dataset.permission;
            const isGranted = this.checked;

            try {
                const response = await fetch('update_permissions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: userId,
                        permission: permission,
                        granted: isGranted
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    alert('Failed to update permission: ' + (data.message || 'Unknown error'));
                    this.checked = !this.checked;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating permission');
                this.checked = !this.checked;
            }
        });
    });
});
