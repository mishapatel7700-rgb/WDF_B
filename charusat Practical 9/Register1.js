const form = document.getElementById("registrationForm");
const message = document.getElementById("formMessage");

form.addEventListener("submit", function (event) {
    message.textContent = "";

    const name = document.getElementById("name").value.trim();
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const mobile = document.getElementById("mobile").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword =
        document.getElementById("confirmPassword").value;

    const course = document.getElementById("course").value;
    const year = document.getElementById("year").value;
    const gender = document.querySelector(
        'input[name="gender"]:checked'
    );
    const terms = document.getElementById("terms").checked;

    function showError(text) {
        event.preventDefault();
        message.textContent = text;
    }

    if (!/^[A-Za-z ]{2,50}$/.test(name)) {
        showError("Name must contain 2–50 letters and spaces.");
        return;
    }

    if (!/^[A-Za-z0-9_]{3,50}$/.test(username)) {
        showError(
            "Username must contain 3–50 letters, numbers, or underscores."
        );
        return;
    }

    if (
        email.length > 254 ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    ) {
        showError("Please enter a valid email address.");
        return;
    }

    if (!/^[6-9][0-9]{9}$/.test(mobile)) {
        showError("Enter a valid 10-digit Indian mobile number.");
        return;
    }

    const passwordRegex =
        /^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z0-9@$!%*?&]{8,72}$/;

    if (!passwordRegex.test(password)) {
        showError(
            "Password must be 8–72 characters and contain a letter, number, and special character (@$!%*?&)."
        );
        return;
    }

    if (password !== confirmPassword) {
        showError("Passwords do not match.");
        return;
    }

    if (!["BCA", "BBA", "B.Tech", "MCA", "MBA"].includes(course)) {
        showError("Please select a valid course.");
        return;
    }

    if (!["1", "2", "3", "4"].includes(year)) {
        showError("Please select a valid year.");
        return;
    }

    if (!gender) {
        showError("Please select your gender.");
        return;
    }

    if (!terms) {
        showError("Please accept the Terms and Conditions.");
    }

});
