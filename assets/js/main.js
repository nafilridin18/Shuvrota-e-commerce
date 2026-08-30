document.addEventListener('DOMContentLoaded', function() {
    // নোটিফিকেশন অ্যালার্ট স্বয়ংক্রিয়ভাবে বন্ধ হওয়া
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4000);
    });

    // চেকআউট ফর্ম মোবাইল নম্বর ভ্যালিডেশন
    const checkoutForm = document.querySelector('#checkoutForm');
    if(checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const phoneInput = document.querySelector('input[name="phone"]');
            const phoneRegex = /^01[3-9]\d{8}$/;
            
            if(!phoneRegex.test(phoneInput.value)) {
                e.preventDefault();
                alert('দয়া করে একটি সঠিক ১১ ডিজিটের বাংলাদেশী মোবাইল নম্বর দিন (যেমন: 01712345678)');
            }
        });
    }
});