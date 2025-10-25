// Doctor Dashboard JavaScript
// Handles appointment management, filtering, and editing

let currentDoctor = null;
let allBookings = [];
let filteredBookings = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if doctor is logged in
    const session = sessionStorage.getItem('doctorSession');
    if (!session) {
        window.location.href = 'doctor_login.html';
        return;
    }
    
    try {
        currentDoctor = JSON.parse(session);
        document.getElementById('doctorName').textContent = currentDoctor.doctorName;
    } catch (e) {
        console.error('Invalid session data');
        window.location.href = 'doctor_login.html';
        return;
    }
    
    // Load initial data
    loadBookings();
    
    // Set up event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Date change handler for time slots
    document.getElementById('editDate').addEventListener('change', function() {
        loadTimeSlots(this.value);
    });
    
    // Form submission
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveAppointmentChanges();
    });
}

// Load bookings from API
async function loadBookings() {
    try {
        showLoading(true);
        
        console.log('Current doctor session:', currentDoctor);
        console.log('Doctor ID:', currentDoctor.doctorId);
        
        // Fetch real data from API
        const apiUrl = `/SmileBright/api/booking/by-doctor.php?doctorId=${encodeURIComponent(currentDoctor.doctorId)}`;
        console.log('API URL:', apiUrl);
        
        const response = await fetch(apiUrl);
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const responseText = await response.text();
        console.log('Raw response:', responseText.substring(0, 200) + '...');
        
        const data = JSON.parse(responseText);
        console.log('Parsed data:', data);
        
        if (data.ok && data.bookings) {
            allBookings = data.bookings;
            applyFilters();
            updateStats();
        } else {
            throw new Error(data.error || 'Failed to load bookings');
        }
        
    } catch (error) {
        console.error('Error loading bookings:', error);
        showError('Failed to load appointments: ' + error.message);
    } finally {
        showLoading(false);
    }
}

// Export appointments to JSON
function exportToJSON() {
    const exportData = {
        doctor: {
            id: currentDoctor.doctorId,
            name: currentDoctor.doctorName,
            exportDate: new Date().toISOString()
        },
        appointments: allBookings.map(booking => ({
            referenceId: booking.referenceId,
            patient: {
                firstName: booking.firstName,
                lastName: booking.lastName,
                fullName: `${booking.firstName} ${booking.lastName}`,
                email: booking.email,
                phone: booking.phone
            },
            appointment: {
                date: booking.dateIso,
                time: booking.time24,
                dateDisplay: formatDate(booking.dateIso),
                timeDisplay: formatTime(booking.time24)
            },
            service: {
                code: booking.serviceCode,
                label: booking.serviceLabel
            },
            experience: {
                code: booking.experienceCode,
                label: booking.experienceLabel
            },
            notes: booking.notes || '',
            status: booking.status,
            clinic: {
                id: booking.clinicId,
                name: booking.clinicName
            },
            dentist: {
                id: booking.dentistId,
                name: booking.dentistName
            },
            timestamps: {
                createdAt: booking.createdAt,
                updatedAt: booking.updatedAt
            }
        })),
        statistics: {
            total: allBookings.length,
            scheduled: allBookings.filter(b => b.status === 'scheduled').length,
            completed: allBookings.filter(b => b.status === 'completed').length,
            cancelled: allBookings.filter(b => b.status === 'cancelled').length,
            today: allBookings.filter(b => b.dateIso === getTodayDate()).length
        }
    };
    
    // Create and download JSON file
    const jsonString = JSON.stringify(exportData, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `doctor_${currentDoctor.doctorId}_appointments_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    console.log('Appointments exported to JSON:', exportData);
}

// Apply filters to bookings
function applyFilters() {
    const statusFilter = document.getElementById('filterStatus').value;
    const dateFilter = document.getElementById('filterDate').value;
    
    filteredBookings = allBookings.filter(booking => {
        let matches = true;
        
        if (statusFilter && booking.status !== statusFilter) {
            matches = false;
        }
        
        if (dateFilter && booking.dateIso !== dateFilter) {
            matches = false;
        }
        
        return matches;
    });
    
    displayBookings();
}

// Display bookings in table
function displayBookings() {
    const tbody = document.getElementById('bookingsBody');
    const noBookingsState = document.getElementById('noBookingsState');
    const bookingsTable = document.getElementById('bookingsTable');
    
    if (filteredBookings.length === 0) {
        noBookingsState.style.display = 'block';
        bookingsTable.style.display = 'none';
        return;
    }
    
    noBookingsState.style.display = 'none';
    bookingsTable.style.display = 'block';
    
    tbody.innerHTML = filteredBookings.map(booking => `
        <tr>
            <td>${booking.referenceId}</td>
            <td>${booking.firstName} ${booking.lastName}</td>
            <td>${formatDate(booking.dateIso)}</td>
            <td>${formatTime(booking.time24)}</td>
            <td>${booking.serviceLabel}</td>
            <td>${booking.experienceLabel || 'Not specified'}</td>
            <td>${booking.notes || 'No additional notes'}</td>
            <td><span class="status-badge status-${booking.status}">${capitalizeFirst(booking.status)}</span></td>
            <td>
                <button class="btn-edit" onclick="editAppointment('${booking.referenceId}')">
                    Edit
                </button>
            </td>
        </tr>
    `).join('');
}

// Update statistics
function updateStats() {
    const total = allBookings.length;
    const scheduled = allBookings.filter(b => b.status === 'scheduled').length;
    const today = allBookings.filter(b => b.dateIso === getTodayDate()).length;
    
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statScheduled').textContent = scheduled;
    document.getElementById('statToday').textContent = today;
}

// Edit appointment
function editAppointment(referenceId) {
    const booking = allBookings.find(b => b.referenceId === referenceId);
    if (!booking) return;
    
    // Populate form
    document.getElementById('editReferenceId').value = booking.referenceId;
    document.getElementById('editPatientName').value = `${booking.firstName} ${booking.lastName}`;
    document.getElementById('editDate').value = booking.dateIso;
    document.getElementById('editTime').value = booking.time24.substring(0, 5); // Remove seconds if present
    document.getElementById('editNotes').value = booking.notes || '';
    
    // Load time slots for the selected date
    loadTimeSlots(booking.dateIso);
    
    // Show modal
    document.getElementById('editModal').classList.add('active');
}

// Load available time slots for a date
async function loadTimeSlots(date) {
    const grid = document.getElementById('timeSlotsGrid');
    
    if (!date) {
        grid.innerHTML = '<div style="text-align: center; color: #6b7a90; padding: 20px;">Select a date to see available times</div>';
        return;
    }
    
    // Show loading state
    grid.innerHTML = '<div style="text-align: center; color: #6b7a90; padding: 20px;">Loading available times...</div>';
    
    try {
        // Fetch real availability from API
        const response = await fetch(`/SmileBright/api/booking/availability.php?clinicId=${encodeURIComponent(currentDoctor.clinicId || 'orchard')}&dentistId=${encodeURIComponent(currentDoctor.doctorId)}&date=${encodeURIComponent(date)}`);
        const data = await response.json();
        
        if (data.ok && data.slots && data.slots.length > 0) {
            const currentTime = document.getElementById('editTime').value;
            
            grid.innerHTML = data.slots.map(time => `
                <div class="time-slot ${time === currentTime ? 'selected' : ''}" 
                     onclick="selectTimeSlot('${time}')">
                    ${formatTime(time)}
                </div>
            `).join('');
        } else {
            grid.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">No available times for this date</div>';
        }
    } catch (error) {
        console.error('Error loading time slots:', error);
        grid.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Error loading times</div>';
    }
}

// Select time slot
function selectTimeSlot(time) {
    // Remove previous selection
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    // Add selection to clicked slot
    event.target.classList.add('selected');
    
    // Update hidden input
    document.getElementById('editTime').value = time;
}

// Save appointment changes
async function saveAppointmentChanges() {
    const referenceId = document.getElementById('editReferenceId').value;
    const newDate = document.getElementById('editDate').value;
    const newTime = document.getElementById('editTime').value;
    const newNotes = document.getElementById('editNotes').value;
    
    try {
        // Find the booking in our data
        const bookingIndex = allBookings.findIndex(b => b.referenceId === referenceId);
        if (bookingIndex === -1) {
            throw new Error('Booking not found');
        }
        
        // Build changes object for API
        const changes = {};
        const originalBooking = allBookings[bookingIndex];
        
        if (newDate !== originalBooking.dateIso) {
            changes.dateIso = newDate;
        }
        if (newTime !== originalBooking.time24.substring(0, 5)) {
            changes.time24 = newTime;
        }
        if (newNotes !== (originalBooking.notes || '')) {
            changes.notes = newNotes;
        }
        
        if (Object.keys(changes).length === 0) {
            alert('No changes detected');
            return;
        }
        
        // Make API call to save changes
        const response = await fetch('/SmileBright/api/booking/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                referenceId: referenceId,
                changes: changes
            })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            alert('Appointment updated successfully! Email notifications sent.');
            
            // Close modal and refresh data
            closeModal();
            loadBookings(); // Reload from API
        } else {
            throw new Error(data.error || 'Update failed');
        }
        
    } catch (error) {
        console.error('Error saving changes:', error);
        alert('Failed to save changes. Please try again.');
    }
}

// Close modal
function closeModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('doctorSession');
        window.location.href = 'doctor_login.html';
    }
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getTodayDate() {
    return new Date().toISOString().split('T')[0];
}

function showLoading(show) {
    const loadingState = document.getElementById('loadingState');
    const noBookingsState = document.getElementById('noBookingsState');
    const bookingsTable = document.getElementById('bookingsTable');
    
    if (show) {
        loadingState.style.display = 'block';
        noBookingsState.style.display = 'none';
        bookingsTable.style.display = 'none';
    } else {
        loadingState.style.display = 'none';
    }
}

function showError(message) {
    alert('Error: ' + message);
}
