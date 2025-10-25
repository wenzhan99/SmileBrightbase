// Test script to verify doctor dashboard loads real data
// This simulates the login process and checks if the dashboard loads Dr. James Lim's appointments

// Simulate login session for Dr. James Lim
const testSession = {
    doctorId: 'dr-james',
    doctorName: 'Dr. James Lim',
    loginTime: new Date().toISOString()
};

// Set session storage
sessionStorage.setItem('doctorSession', JSON.stringify(testSession));

console.log('Test session set for Dr. James Lim');
console.log('Session data:', testSession);

// Test API call
fetch('/api/booking/by-doctor.php?doctorId=dr-james')
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        console.log('Number of bookings:', data.bookings ? data.bookings.length : 0);
        
        if (data.bookings && data.bookings.length > 0) {
            console.log('Sample booking:', data.bookings[0]);
            console.log('✅ Dashboard should show real data for Dr. James Lim');
        } else {
            console.log('❌ No bookings found for Dr. James Lim');
        }
    })
    .catch(error => {
        console.error('API Error:', error);
    });
