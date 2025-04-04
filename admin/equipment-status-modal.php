<?php
// equipment-status-modal.php
ob_start(); 
session_start();
include "../includes/header.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

// Get admin email
$admin_email = $_SESSION['email'] ?? '';
?>

<div id="equipmentStatusModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="modalTitle">Change Equipment Status</h3>
        <form id="statusChangeForm">
            <input type="hidden" id="inventoryId" name="inventory_id">
            <input type="hidden" id="actionType" name="action_type">
            
            <div class="form-group">
                <label for="reason">Reason for this action:</label>
                <textarea id="reason" name="reason" rows="4" required class="form-control"></textarea>
            </div>
            
            <div class="form-group">
                <label for="notifyAdmin">Notify:</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="notifyAdmin" name="notify_admin" checked>
                    <label for="notifyAdmin">Current Admin (<?= htmlspecialchars($admin_email) ?>)</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="notifyMain" name="notify_main" checked>
                    <label for="notifyMain">Main Account (flexifit04@gmail.com)</label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-confirm">Confirm</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #1e1e1e;
    padding: 25px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    border: 1px solid #FFD700;
    position: relative;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    color: #aaa;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #FFD700;
}

#modalTitle {
    color: #FFD700;
    margin-top: 0;
    margin-bottom: 20px;
    text-align: center;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #f8f8f8;
}

.form-control {
    width: 100%;
    padding: 10px;
    background-color: #2a2a2a;
    border: 1px solid #444;
    border-radius: 4px;
    color: #f8f8f8;
    resize: vertical;
}

.checkbox-group {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.checkbox-group input {
    margin-right: 10px;
}

.checkbox-group label {
    margin: 0;
    color: #ccc;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-cancel {
    background-color: #444;
    color: #f8f8f8;
    border: none;
}

.btn-cancel:hover {
    background-color: #555;
}

.btn-confirm {
    background-color: #FFD700;
    color: #000;
    border: none;
}

.btn-confirm:hover {
    background-color: #e6c200;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    const modal = document.getElementById('equipmentStatusModal');
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.btn-cancel');
    
    function closeModalWindow() {
        modal.style.display = 'none';
    }
    
    closeModal.addEventListener('click', closeModalWindow);
    cancelBtn.addEventListener('click', closeModalWindow);
    
    // Handle form submission
    const statusForm = document.getElementById('statusChangeForm');
    
    statusForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const inventoryId = formData.get('inventory_id');
        const actionType = formData.get('action_type');
        const reason = formData.get('reason');
        const notifyAdmin = formData.get('notify_admin') === 'on';
        const notifyMain = formData.get('notify_main') === 'on';
        
        fetch('process-equipment-status.php', {
            method: 'POST',
            body: JSON.stringify({
                inventory_id: inventoryId,
                action_type: actionType,
                reason: reason,
                notify_admin: notifyAdmin,
                notify_main: notifyMain
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
    
    // Make modal accessible globally
    window.showStatusModal = function(inventoryId, actionType) {
        document.getElementById('inventoryId').value = inventoryId;
        document.getElementById('actionType').value = actionType;
        document.getElementById('modalTitle').textContent = 
            actionType === 'disable' ? 'Disable Equipment' : 'Enable Equipment';
        modal.style.display = 'flex';
    };
});
</script>