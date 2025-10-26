// Doctor Dashboard JavaScript - Comprehensive Version

let currentDoctor = null;
let currentBooking = null;
let allBookings = [];

// Check authentication on page load
document.addEventListener('DOMContentLoaded', function() {
  checkAuth();
  loadBookings();
  
  // Set up event listeners
  document.getElementById('editDate').addEventListener('change', loadAvailableTimes);
  document.getElementById('editForm').addEventListener('submit', handleUpdate);
  
  // Event delegation for Edit buttons (fixes broken onclick handlers)
  document.getElementById('bookingsBody').addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-edit')) {
      const refId = e.target.getAttribute('data-ref-id');
      const booking = allBookings.find(b => b.referenceId === refId);
      if (booking) {
        openEditModal(booking);
      }
    }
  });
});

    // Check if doctor is logged in
function checkAuth() {
    const session = sessionStorage.getItem('doctorSession');
  
    if (!session) {
        window.location.href = 'doctor_login.html';
        return;
    }
    
    try {
        currentDoctor = JSON.parse(session);
        document.getElementById('doctorName').textContent = currentDoctor.doctorName;
    } catch (e) {
    console.error('Invalid session:', e);
    logout();
  }
}

// Logout
function logout() {
  sessionStorage.removeItem('doctorSession');
  window.location.href = 'doctor_login.html';
}

// Load bookings from API
async function loadBookings() {
  if (!currentDoctor) return;
  
  const status = document.getElementById('filterStatus').value;
  const urgency = document.getElementById('filterUrgency').value;
  const date = document.getElementById('filterDate').value;
  
  // Show loading
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('noBookingsState').style.display = 'none';
  document.getElementById('bookingsTable').style.display = 'none';
  
  try {
    // Use relative path from /public/booking/ to /api/booking/
    let url = `../../api/booking/by-doctor.php?doctorId=${encodeURIComponent(currentDoctor.doctorId)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;
    if (date) url += `&date=${encodeURIComponent(date)}`;
    
    console.log('Loading bookings for doctor:', currentDoctor.doctorId);
    console.log('API URL:', url);
    
    const response = await fetch(url);
    const data = await response.json();
    
    console.log('API Response:', data);
    console.log('Number of bookings returned:', data.bookings?.length || 0);
    
    if (data.ok && data.bookings) {
      // Transform API data to match comprehensive schema
      allBookings = data.bookings.map(booking => transformBookingData(booking));
      
      console.log('Transformed bookings:', allBookings);
      
      // Apply client-side urgency filter (if API doesn't support it)
      let filteredBookings = allBookings;
      if (urgency) {
        filteredBookings = filteredBookings.filter(b => 
          b.urgency.toLowerCase() === urgency.toLowerCase()
        );
        console.log(`Filtered by urgency "${urgency}":`, filteredBookings.length, 'appointments');
      }
      
      displayBookings(filteredBookings);
      updateStats(allBookings); // Stats based on all bookings
    } else {
      throw new Error(data.error || 'Failed to load bookings');
    }
    } catch (error) {
        console.error('Error loading bookings:', error);
    document.getElementById('loadingState').textContent = 'Error loading appointments. Please refresh the page.';
  }
}

// Transform API booking data to comprehensive format
function transformBookingData(booking) {
  return {
    referenceId: booking.referenceId || '',
    patient: {
      id: booking.patientId || '',
      name: `${booking.firstName || ''} ${booking.lastName || ''}`.trim()
    },
    date: booking.dateIso || '',
    // Display time in 24-hour format (e.g., 09:00, 14:00, 15:00)
    time: booking.time24 ? booking.time24.substring(0, 5) : '',
    time24: booking.time24 || '',
    dateTime: booking.dateIso && booking.time24 ? `${booking.dateIso}T${booking.time24}:00+08:00` : '',
    service: booking.serviceLabel || booking.service || '',
    urgency: booking.urgency || 'Routine',
    lastSeen: booking.lastSeen || null,
    recallDue: booking.recallDue || null,
    previousDentalExperience: booking.previousDentalExperience || booking.previousExperience || '',
    medicalFlags: booking.medicalFlags || [],
    medicalFlagsNA: !booking.medicalFlags || booking.medicalFlags.length === 0,
    additionalNotes: booking.additionalNotes || booking.notes || '',
    status: normalizeStatus(booking.status),
    // Keep original data for API compatibility
    _original: booking
  };
}

// Normalize status from API to comprehensive format
function normalizeStatus(status) {
  const statusMap = {
    'scheduled': 'Confirmed',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'booked': 'Booked',
    'confirmed': 'Confirmed',
    'checked-in': 'Checked-In',
    'in progress': 'In Progress',
    'no-show': 'No-Show',
    'rescheduled': 'Rescheduled'
  };
  return statusMap[status?.toLowerCase()] || 'Booked';
}

// Format 24-hour time to 12-hour display
function formatTime24(time24) {
  if (!time24) return '';
  const [hours, minutes] = time24.split(':');
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
}

// Format date for display
function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr + 'T00:00:00');
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Display bookings in table
function displayBookings(bookings) {
    const tbody = document.getElementById('bookingsBody');
  
  document.getElementById('loadingState').style.display = 'none';
  
  if (bookings.length === 0) {
    document.getElementById('noBookingsState').style.display = 'block';
    document.getElementById('bookingsTable').style.display = 'none';
        return;
    }
    
  document.getElementById('noBookingsState').style.display = 'none';
  document.getElementById('bookingsTable').style.display = 'block';
  
  tbody.innerHTML = '';
  
  bookings.forEach(booking => {
    const row = document.createElement('tr');
    
    // Medical Flags HTML
    let medicalFlagsHtml = '';
    if (booking.medicalFlagsNA) {
      medicalFlagsHtml = '<div class="medical-flags"><span class="flag-badge flag-na">✓ N/A</span></div>';
    } else if (booking.medicalFlags && booking.medicalFlags.length > 0) {
      medicalFlagsHtml = '<div class="medical-flags">' + 
        booking.medicalFlags.map(flag => `<span class="flag-badge">${escapeHtml(flag)}</span>`).join('') +
        '</div>';
    } else {
      medicalFlagsHtml = '<div class="medical-flags"><span class="flag-badge flag-na">✓ N/A</span></div>';
    }
    
    // Status class
    const statusClass = 'status-' + booking.status.toLowerCase().replace(/\s+/g, '-');
    
    // Urgency class
    const urgencyClass = 'urgency-' + booking.urgency.toLowerCase();
    
    row.innerHTML = `
      <td><strong>${escapeHtml(booking.referenceId)}</strong></td>
      <td>${escapeHtml(booking.patient.name)}</td>
            <td>${formatDate(booking.date)}</td>
      <td>${escapeHtml(booking.time)}</td>
      <td>${escapeHtml(booking.service)}</td>
      <td><span class="urgency-badge ${urgencyClass}">${escapeHtml(booking.urgency)}</span></td>
      <td>${booking.lastSeen ? formatDate(booking.lastSeen) : '-'}</td>
      <td>${booking.recallDue ? formatDate(booking.recallDue) : '-'}</td>
      <td class="cell-expand" title="${escapeHtml(booking.previousDentalExperience)}">${escapeHtml(booking.previousDentalExperience) || '-'}</td>
      <td>${medicalFlagsHtml}</td>
      <td class="cell-expand" title="${escapeHtml(booking.additionalNotes)}">${escapeHtml(booking.additionalNotes) || '-'}</td>
      <td><span class="status-badge ${statusClass}">${escapeHtml(booking.status)}</span></td>
      <td>
        <button class="btn-edit" data-ref-id="${escapeHtml(booking.referenceId)}">Edit</button>
            </td>
    `;
    
    tbody.appendChild(row);
  });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Update statistics
function updateStats(bookings) {
  const today = new Date().toISOString().split('T')[0];
  
  const scheduledCount = bookings.filter(b => 
    ['Booked', 'Confirmed', 'Checked-In'].includes(b.status)
  ).length;
  const todayCount = bookings.filter(b => b.date === today).length;
  
  document.getElementById('statTotal').textContent = bookings.length;
  document.getElementById('statScheduled').textContent = scheduledCount;
  document.getElementById('statToday').textContent = todayCount;
}

// Open edit modal
function openEditModal(booking) {
  currentBooking = booking;
    
    // Populate form
    document.getElementById('editReferenceId').value = booking.referenceId;
  document.getElementById('editPatientName').value = booking.patient.name;
    document.getElementById('editDate').value = booking.date;
  document.getElementById('editService').value = booking.service;
  document.getElementById('editUrgency').value = booking.urgency;
  document.getElementById('editStatus').value = booking.status;
  document.getElementById('editLastSeen').value = booking.lastSeen || '';
  document.getElementById('editRecallDue').value = booking.recallDue || '';
  document.getElementById('editPreviousExperience').value = booking.previousDentalExperience || '';
  document.getElementById('editAdditionalNotes').value = booking.additionalNotes || '';
  
  // Medical flags
  if (booking.medicalFlags && booking.medicalFlags.length > 0) {
    document.getElementById('editMedicalFlags').value = booking.medicalFlags.join(', ');
  } else {
    document.getElementById('editMedicalFlags').value = '';
  }
  
  // Normalize time format
  let currentTime = booking.time24;
  if (currentTime && currentTime.length > 5) {
    currentTime = currentTime.substring(0, 5);
  }
  document.getElementById('editTime').value = currentTime;
  
  // Load available times
  loadAvailableTimes(currentTime);
    
    // Show modal
    document.getElementById('editModal').classList.add('active');
}

// Close modal
function closeModal() {
  document.getElementById('editModal').classList.remove('active');
  currentBooking = null;
}

// Fixed time slots as per scheduling constraints
const FIXED_TIME_SLOTS = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

// Load available times
async function loadAvailableTimes(preSelectTime = null) {
  if (!currentDoctor || !currentBooking) return;
  
  const date = document.getElementById('editDate').value;
  const timeSlotsGrid = document.getElementById('timeSlotsGrid');
    
    if (!date) {
    timeSlotsGrid.innerHTML = '<div style="text-align: center; color: #6b7a90; padding: 20px;">Please select a date</div>';
        return;
    }
    
  timeSlotsGrid.innerHTML = '<div style="text-align: center; color: #6b7a90; padding: 20px;">Loading available times...</div>';
  
  try {
    // Use clinic ID from original booking data if available
    const clinicId = currentBooking._original?.clinicId || 'orchard';
    // Use relative path from /public/booking/ to /api/booking/
    const response = await fetch(`../../api/booking/availability.php?clinicId=${encodeURIComponent(clinicId)}&dentistId=${encodeURIComponent(currentDoctor.doctorId)}&date=${encodeURIComponent(date)}`);
    const data = await response.json();
    
    // Always use the 7 fixed time slots, regardless of API response
    // Filter API slots to only include our fixed slots (for future compatibility)
    let availableSlots = FIXED_TIME_SLOTS;
    
    // If API provides availability data, filter to only show available slots from our fixed set
    if (data.ok && data.slots && data.slots.length > 0) {
      // Normalize API slots to HH:mm format
      const normalizedApiSlots = data.slots.map(slot => {
        // Handle both "HH:mm" and "HH:mm:ss" formats
        return slot.substring(0, 5);
      });
      
      // Only show fixed slots that are available according to API
      availableSlots = FIXED_TIME_SLOTS.filter(slot => normalizedApiSlots.includes(slot));
      
      // If no slots match, fall back to showing all 7 fixed slots
      if (availableSlots.length === 0) {
        availableSlots = FIXED_TIME_SLOTS;
      }
    }
    
    const currentValue = typeof preSelectTime === 'string' ? preSelectTime.substring(0, 5) : document.getElementById('editTime').value;
    
    timeSlotsGrid.innerHTML = '';
    
    let timeSelected = false;
    
    availableSlots.forEach(time => {
      const slotElement = document.createElement('div');
      slotElement.className = 'time-slot';
      
      // Display time in 24-hour format (e.g., 09:00, 14:00)
      slotElement.textContent = time;
      
      slotElement.onclick = () => selectTimeSlot(time, slotElement);
      
      // Pre-select current time if it matches
      if (currentValue && time === currentValue) {
        slotElement.classList.add('selected');
        document.getElementById('editTime').value = time;
        timeSelected = true;
      }
      
      timeSlotsGrid.appendChild(slotElement);
    });
    
    // If no time was pre-selected, select the first slot
    if (!timeSelected && availableSlots.length > 0) {
      const firstSlot = timeSlotsGrid.querySelector('.time-slot');
      if (firstSlot) {
        firstSlot.classList.add('selected');
        document.getElementById('editTime').value = availableSlots[0];
      }
    }
    
    // Add note about fixed time slots
    const noteElement = document.createElement('div');
    noteElement.style.cssText = 'grid-column: 1/-1; text-align: center; color: #6b7a90; font-size: 0.85rem; margin-top: 10px;';
    noteElement.textContent = 'Only 7 fixed time slots available: 09:00, 10:00, 11:00, 14:00, 15:00, 16:00, 17:00';
    timeSlotsGrid.appendChild(noteElement);
    
  } catch (error) {
    console.error('Error loading times:', error);
    // Even on error, show the fixed time slots
    timeSlotsGrid.innerHTML = '';
    
    const currentValue = typeof preSelectTime === 'string' ? preSelectTime.substring(0, 5) : document.getElementById('editTime').value;
    let timeSelected = false;
    
    FIXED_TIME_SLOTS.forEach(time => {
      const slotElement = document.createElement('div');
      slotElement.className = 'time-slot';
      // Display time in 24-hour format (e.g., 09:00, 14:00)
      slotElement.textContent = time;
      slotElement.onclick = () => selectTimeSlot(time, slotElement);
      
      if (currentValue && time === currentValue) {
        slotElement.classList.add('selected');
    document.getElementById('editTime').value = time;
        timeSelected = true;
      }
      
      timeSlotsGrid.appendChild(slotElement);
    });
    
    if (!timeSelected && FIXED_TIME_SLOTS.length > 0) {
      const firstSlot = timeSlotsGrid.querySelector('.time-slot');
      if (firstSlot) {
        firstSlot.classList.add('selected');
        document.getElementById('editTime').value = FIXED_TIME_SLOTS[0];
      }
    }
  }
}

// Select time slot
function selectTimeSlot(time, element) {
  document.querySelectorAll('.time-slot.selected').forEach(el => {
    el.classList.remove('selected');
  });
  
  element.classList.add('selected');
  document.getElementById('editTime').value = time;
}

// Handle update
async function handleUpdate(e) {
  e.preventDefault();
  
  if (!currentBooking) return;
  
  const referenceId = document.getElementById('editReferenceId').value;
  const dateIso = document.getElementById('editDate').value;
  const time24 = document.getElementById('editTime').value;
  const status = document.getElementById('editStatus').value;
  const additionalNotes = document.getElementById('editAdditionalNotes').value;
  
  // These fields exist in UI but not in database yet - skip for now
  // const urgency = document.getElementById('editUrgency').value;
  // const lastSeen = document.getElementById('editLastSeen').value;
  // const recallDue = document.getElementById('editRecallDue').value;
  // const previousExperience = document.getElementById('editPreviousExperience').value;
  // const medicalFlagsInput = document.getElementById('editMedicalFlags').value;
  
  if (!time24) {
    alert('Please select a time');
    return;
  }
  
  // Validate time slot is one of the 7 fixed slots
  const normalizedTime = time24.substring(0, 5);
  if (!FIXED_TIME_SLOTS.includes(normalizedTime)) {
    alert(`Invalid time slot. Please select from: ${FIXED_TIME_SLOTS.join(', ')}`);
    return;
  }
  
  // Build changes object (only send fields that exist in database)
  const changes = {};
  
  // Date change
  if (dateIso !== currentBooking.date) {
    changes.dateIso = dateIso;
  }
  
  // Time change - normalize to HH:mm format
  const currentTime24 = currentBooking.time24 ? currentBooking.time24.substring(0, 5) : '';
  if (normalizedTime !== currentTime24) {
    changes.time24 = normalizedTime; // Always send HH:mm format
  }
  
  // Status change - normalize to lowercase for DB
  const currentStatus = (currentBooking.status || '').toLowerCase();
  const newStatus = status.toLowerCase();
  if (newStatus !== currentStatus) {
    changes.status = newStatus; // Send lowercase: cancelled, completed, etc.
  }
  
  // Notes change
  if (additionalNotes !== (currentBooking.additionalNotes || '')) {
    changes.additionalNotes = additionalNotes; // API will map to 'notes'
  }
  
  // Future: Add these fields when database is updated
  // if (urgency !== currentBooking.urgency) changes.urgency = urgency;
  // if (lastSeen) changes.lastSeen = lastSeen;
  // if (recallDue) changes.recallDue = recallDue;
  // if (previousExperience) changes.previousDentalExperience = previousExperience;
  // if (medicalFlags) changes.medicalFlags = medicalFlags;
  
  if (Object.keys(changes).length === 0) {
    alert('No changes detected');
    return;
  }
  
  // Validate required fields
  if (!referenceId) {
    alert('Error: Missing reference ID');
    return;
  }
  
  // Disable submit button
  const submitBtn = e.target.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Saving...';
  
  // Log the update request for debugging
  console.log('Updating appointment:', {
    referenceId: referenceId,
    changes: changes,
    doctor: currentDoctor.doctorId
  });
  
  try {
    // Use relative path from /public/booking/ to /api/booking/
    const response = await fetch('../../api/booking/update.php', {
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
    
    console.log('Update response:', data);
    
    if (data.ok) {
      alert('Appointment updated successfully!' + (data.emailSent ? ' Email notifications sent.' : ''));
      closeModal();
      loadBookings(); // Reload the bookings list
    } else {
      throw new Error(data.error || 'Update failed');
    }
  } catch (error) {
    console.error('Error updating booking:', error);
    alert('Failed to update appointment: ' + error.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Save Changes';
  }
}
