document.addEventListener('DOMContentLoaded', function() {
    // নোটিফিকেশন এলার্ট হাইড করা
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4000);
    });

    // চেকআউট ফর্মে ফোন নম্বর ভ্যালিডেশন
    const checkoutForm = document.querySelector('form[action="checkout.php"]');
    if(checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const phoneInput = document.querySelector('input[name="phone"]');
            const phoneRegex = /^01[3-9]\d{8}$/;
            
            if(!phoneRegex.test(phoneInput.value)) {
                e.preventDefault();
                alert('দয়া করে সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন: 01712345678)');
            }
        });
    }
});