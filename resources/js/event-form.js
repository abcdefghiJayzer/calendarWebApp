document.addEventListener('DOMContentLoaded', function() {
    const addEventForm = document.getElementById('add-event-form');
    const editEventForm = document.getElementById('edit-event-form');

    function validateOrganizationalUnits(form) {
        const isGlobal = form.querySelector('input[name="is_global"]').checked;
        const selectedUnits = form.querySelectorAll('input[name="organizational_unit_ids[]"]:checked');

        if (!isGlobal && selectedUnits.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select at least one organizational unit or make the event global',
                confirmButtonColor: '#22c55e'
            });
            return false;
        }
        return true;
    }

    function initializeCheckboxes() {
        // Global checkbox
        const globalCheckbox = document.getElementById('is_global');
        if (globalCheckbox) {
            globalCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="organizational_unit_ids[]"]');
                const orgUnitsContainer = document.getElementById('organizationalUnitsContainer');

                if (this.checked) {
                    // If global is checked, uncheck and disable all other checkboxes
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        checkbox.disabled = true;
                    });
                    if (orgUnitsContainer) {
                        orgUnitsContainer.style.display = 'none';
                    }
                } else {
                    // If global is unchecked, enable all checkboxes
                    checkboxes.forEach(checkbox => {
                        checkbox.disabled = false;
                    });
                    if (orgUnitsContainer) {
                        orgUnitsContainer.style.display = 'block';
                    }
                }
                updateSelectedUnitsText();
            });
        }

        // Sector checkboxes
        document.querySelectorAll('.sector-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const sectorId = this.dataset.sectorId;
                const divisionCheckboxes = document.querySelectorAll(`.division-checkbox[data-sector-id="${sectorId}"]`);

                if (this.checked) {
                    // If sector is checked, check all its divisions but uncheck the sector itself
                    this.checked = false;
                    divisionCheckboxes.forEach(divCheckbox => {
                        divCheckbox.checked = true;
                    });
                } else {
                    // If sector is unchecked, uncheck all its divisions
                    divisionCheckboxes.forEach(divCheckbox => {
                        divCheckbox.checked = false;
                    });
                }
                updateSelectedUnitsText();
            });
        });

        // Division checkboxes
        document.querySelectorAll('.division-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const sectorId = this.dataset.sectorId;
                const sectorCheckbox = document.querySelector(`.sector-checkbox[data-sector-id="${sectorId}"]`);
                const divisionCheckboxes = document.querySelectorAll(`.division-checkbox[data-sector-id="${sectorId}"]`);

                if (sectorCheckbox) {
                    // If all divisions are checked, uncheck the sector (since we don't want to select the sector itself)
                    const allChecked = Array.from(divisionCheckboxes).every(cb => cb.checked);
                    sectorCheckbox.checked = false;
                }
                updateSelectedUnitsText();
            });
        });
    }

    function updateSelectedUnitsText() {
        const selectedText = document.getElementById('selectedUnitsText');
        const isGlobal = document.getElementById('is_global')?.checked;
        const selectedUnits = document.querySelectorAll('input[name="organizational_unit_ids[]"]:checked');

        if (isGlobal) {
            selectedText.textContent = 'Global Event (Visible to Everyone)';
        } else if (selectedUnits.length === 0) {
            selectedText.textContent = 'Select organizational units';
        } else {
            const unitNames = Array.from(selectedUnits).map(checkbox => {
                const label = checkbox.nextElementSibling;
                return label ? label.textContent.trim() : '';
            }).filter(name => name);
            selectedText.textContent = unitNames.join(', ');
        }
    }

    async function handleOverlappingEvents(data, formData, isEdit = false) {
        // Format the conflict information for display
        let conflictHtml = 'This time slot overlaps with the following priority events:<br><br>';

        data.overlapping_events.forEach(event => {
            const startDate = new Date(event.start_date).toLocaleString();
            const endDate = new Date(event.end_date).toLocaleString();
            conflictHtml += `<strong>${event.title}</strong><br>`;
            conflictHtml += `From: ${startDate}<br>`;
            conflictHtml += `To: ${endDate}<br>`;
            if (event.organizational_units && event.organizational_units.length > 0) {
                conflictHtml += `Organizational Units: ${event.organizational_units.join(', ')}<br>`;
            }
            conflictHtml += '<br>';
        });

        // Show confirmation dialog with options
        const result = await Swal.fire({
            title: 'Priority Event Conflict',
            html: conflictHtml,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#22c55e',
            denyButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Proceed anyway',
            denyButtonText: 'Edit',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            // User wants to proceed despite conflicts
            try {
                // Add parameter to force creation despite overlaps
                formData.append(isEdit ? 'force_update' : 'force_create', 'true');

                const response = await fetch(isEdit ? `${window.baseUrl}/events/${formData.get('id')}` : `${window.baseUrl}/events`, {
                    method: isEdit ? 'POST' : 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const responseData = await response.json();

                if (response.ok) {
                    if (isEdit) {
                        closeEditModal();
                    } else {
                        closeModal();
                    }
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }

                    // Call syncAllToGoogle directly
                    if (typeof window.syncAllToGoogle === 'function') {
                        window.syncAllToGoogle();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: responseData.error || responseData.message || (isEdit ? 'Failed to update event' : 'Failed to create event'),
                        confirmButtonColor: '#22c55e'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was a problem with the request. Please try again.',
                    confirmButtonColor: '#22c55e'
                });
            }
        }
    }

    async function checkGuestConflicts(guests, startDate, endDate, eventId = null) {
        if (!guests || guests.length === 0) return null;

        // Validate email format for all guests
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const invalidEmails = guests.filter(email => !emailRegex.test(email));
        
        if (invalidEmails.length > 0) {
            Swal.fire({
                title: 'Invalid Email Format',
                html: `The following email addresses are invalid:<br><br>${invalidEmails.join('<br>')}`,
                icon: 'error',
                confirmButtonColor: '#22c55e'
            });
            return null;
        }

        const checkData = new URLSearchParams();
        checkData.append('guests', JSON.stringify(guests));
        checkData.append('start_date', startDate);
        checkData.append('end_date', endDate);
        checkData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        if (eventId) {
            checkData.append('event_id', eventId);
        }

        try {
            const response = await fetch('/check-conflicts', {
                method: 'POST',
                body: checkData,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const result = await response.json();
            return result.conflicts;
        } catch (error) {
            console.error('Error checking guest conflicts:', error);
            Swal.fire({
                title: 'Error',
                text: 'Failed to check for guest conflicts. Please try again.',
                icon: 'error',
                confirmButtonColor: '#22c55e'
            });
            return null;
        }
    }

    async function handleGuestConflicts(conflicts) {
        if (!conflicts || conflicts.length === 0) return true;

        let conflictMessage = '<div class="text-left">';
        conflictMessage += '<p class="mb-4">The following guests have scheduling conflicts:</p>';
        
        conflicts.forEach(conflict => {
            conflictMessage += `<div class="mb-4 p-3 bg-red-50 rounded-lg">`;
            conflictMessage += `<p class="font-bold text-red-700">${conflict.email}</p>`;
            conflictMessage += '<p class="mt-2">Conflicts with:</p>';
            conflictMessage += '<ul class="list-disc pl-5 mt-2">';
            
            conflict.events.forEach(event => {
                const start = new Date(event.start).toLocaleString();
                const end = new Date(event.end).toLocaleString();
                conflictMessage += `<li class="text-gray-700">
                    <span class="font-semibold">${event.title}</span><br>
                    <span class="text-sm">${start} to ${end}</span>
                </li>`;
            });
            
            conflictMessage += '</ul></div>';
        });

        conflictMessage += '</div>';

        const result = await Swal.fire({
            title: 'Guest Schedule Conflicts',
            html: conflictMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Proceed anyway',
            cancelButtonText: 'Cancel',
            customClass: {
                container: 'guest-conflict-modal'
            }
        });

        return result.isConfirmed;
    }

    function openEditModal(event) {
        const modal = document.getElementById('edit-event-modal');
        const form = document.getElementById('edit-event-form');
        
        // Set the event ID
        document.getElementById('edit-event-id').value = event.id;
        
        // Set the color
        const colorInput = document.getElementById('edit-event-color');
        const user = window.userInfo;
        if (user.division !== 'institute' && !user.isDivisionHead) {
            colorInput.value = '#616161'; // Always grey for employees
        } else {
            colorInput.value = event.backgroundColor || '#616161';
        }

        // Set other form fields
        // ... existing code ...
    }

    // Modify the form submission handlers
    if (addEventForm) {
        addEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateOrganizationalUnits(this)) {
                return;
            }

            const guests = JSON.parse(this.querySelector('input[name="guests"]').value || '[]');
            const startDate = this.querySelector('input[name="start_date"]').value;
            const endDate = this.querySelector('input[name="end_date"]').value || startDate;

            const conflicts = await checkGuestConflicts(guests, startDate, endDate);
            if (conflicts && !(await handleGuestConflicts(conflicts))) {
                return;
            }

            this.submit();
        });
        initializeCheckboxes();
    }

    if (editEventForm) {
        editEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateOrganizationalUnits(this)) {
                return;
            }

            const guests = JSON.parse(this.querySelector('input[name="guests"]').value || '[]');
            const startDate = this.querySelector('input[name="start_date"]').value;
            const endDate = this.querySelector('input[name="end_date"]').value || startDate;
            const eventId = this.querySelector('input[name="id"]').value;

            const conflicts = await checkGuestConflicts(guests, startDate, endDate, eventId);
            if (conflicts && !(await handleGuestConflicts(conflicts))) {
                return;
            }

            this.submit();
        });
        initializeCheckboxes();
    }
});
