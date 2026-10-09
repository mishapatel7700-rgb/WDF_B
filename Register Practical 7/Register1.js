
document.getElementById("registrationForm").addEventListener("submit", function(event) {

    event.preventDefault();

    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let mobile = document.getElementById("mobile").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;
    let course = document.getElementById("course").value;
    let year = document.getElementById("year").value;
    let terms = document.getElementById("terms").checked;

    let nameRegex = /^[A-Za-z ]{2,50}$/;
    let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let mobileRegex = /^[6-9][0-9]{9}$/;
    let passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    if (!nameRegex.test(name)) {
        alert("Please enter a valid name.");
        return;
    }
    if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return;
    }
    if (!mobileRegex.test(mobile)) {
        alert("Please enter a valid 10-digit mobile number.");
        return;
    }
    if (!passwordRegex.test(password)) {
        alert("Password must contain at least 8 characters, one letter, one number and one special character.");
        return;
    }
    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }
    if (course === "") {
        alert("Please select a course.");
        return;
    }
    if (year === "") {
        alert("Please select your year.");
        return;
    }
    let gender = document.querySelector('input[name="gender"]:checked');

    if (!gender) {
        alert("Please select your gender.");
        return;
    }
    if (!terms) {
        alert("Please accept the Terms and Conditions.");
        return;
    }
alert("Registration successful!");
window.location.href = "Login.html";

});
