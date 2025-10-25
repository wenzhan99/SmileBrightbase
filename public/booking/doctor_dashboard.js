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
        
        // For demo purposes, we'll use mock data
        // In production, this would fetch from your API
        const mockBookings = generateMockBookings();
        
        allBookings = mockBookings;
        applyFilters();
        updateStats();
        
    } catch (error) {
        console.error('Error loading bookings:', error);
        showError('Failed to load appointments');
    } finally {
        showLoading(false);
    }
}

// Generate mock data for demonstration
function generateMockBookings() {
    const today = new Date();
    const bookings = [];
    
    // Generate some sample appointments
    const sampleAppointments = [
        {
            referenceId: 'SB-20250125-001',
            patientName: 'John Smith',
            date: '2025-01-25',
            time: '09:00',
            service: 'General Checkup',
            status: 'scheduled',
            notes: 'Regular checkup'
        },
        {
            referenceId: 'SB-20250125-002',
            patientName: 'Sarah Johnson',
            date: '2025-01-25',
            time: '10:30',
            service: 'Teeth Cleaning',
            status: 'scheduled',
            notes: 'First visit'
        },
        {
            referenceId: 'SB-20250125-003',
            patientName: 'Mike Chen',
            date: '2025-01-26',
            time: '14:00',
            service: 'Dental Filling',
            status: 'scheduled',
            notes: 'Follow-up appointment'
        },
        {
            referenceId: 'SB-20250124-001',
            patientName: 'Emily Davis',
            date: '2025-01-24',
            time: '11:00',
            service: 'Braces Consultation',
            status: 'completed',
            notes: 'Initial consultation completed'
        },
        {
            referenceId: 'SB-20250123-001',
            patientName: 'David Wilson',
            date: '2025-01-23',
            time: '15:30',
            service: 'Tooth Extraction',
            status: 'cancelled',
            notes: 'Patient cancelled due to illness'
        }
    ];
    
    return sampleAppointments;
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
        
        if (dateFilter && booking.date !== dateFilter) {
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
            <td>${booking.patientName}</td>
            <td>${formatDate(booking.date)}</td>
            <td>${formatTime(booking.time)}</td>
            <td>${booking.service}</td>
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
    const today = allBookings.filter(b => b.date === getTodayDate()).length;
    
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
    document.getElementById('editPatientName').value = booking.patientName;
    document.getElementById('editDate').value = booking.date;
    document.getElementById('editTime').value = booking.time;
    document.getElementById('editNotes').value = booking.notes || '';
    
    // Load time slots for the selected date
    loadTimeSlots(booking.date);
    
    // Show modal
    document.getElementById('editModal').classList.add('active');
}

// Load available time slots for a date
function loadTimeSlots(date) {
    const grid = document.getElementById('timeSlotsGrid');
    
    if (!date) {
        grid.innerHTML = '<div style="text-align: center; color: #6b7a90; padding: 20px;">Select a date to see available times</div>';
        return;
    }
    
    // Generate time slots (9 AM to 5 PM, 30-minute intervals)
    const timeSlots = [];
    for (let hour = 9; hour <= 17; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            timeSlots.push(timeString);
        }
    }
    
    const currentTime = document.getElementById('editTime').value;
    
    grid.innerHTML = timeSlots.map(time => `
        <div class="time-slot ${time === currentTime ? 'selected' : ''}" 
             onclick="selectTimeSlot('${time}')">
            ${formatTime(time)}
        </div>
    `).join('');
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
        
        // Update the booking
        allBookings[bookingIndex].date = newDate;
        allBookings[bookingIndex].time = newTime;
        allBookings[bookingIndex].notes = newNotes;
        
        // In production, this would make an API call to save changes
        console.log('Saving changes:', {
            referenceId,
            newDate,
            newTime,
            newNotes
        });
        
        // Show success message
        alert('Appointment updated successfully!');
        
        // Close modal and refresh data
        closeModal();
        applyFilters();
        updateStats();
        
        // In production, you would also trigger email notifications here
        
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
