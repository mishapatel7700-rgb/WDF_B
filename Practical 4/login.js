function verifyEmail() {
    let enteredEmail = document.getElementById("email").value;
    let registeredEmail = localStorage.getItem("registeredEmail");

    if (enteredEmail === "") {
        return;
    }

    if (registeredEmail === null) {
        alert("No registered email found. Please register first.");
    } 
    else if (enteredEmail.toLowerCase() === registeredEmail.toLowerCase()) {
        alert("Email verified successfully!");
    } 
    else {
        alert("Email does not match the registered email.");
    }
}

function login() {
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;
    let registeredEmail = localStorage.getItem("registeredEmail");

    if (email === "" || password === "") {
        alert("Please enter all details.");
        return false;
    }

    if (email.toLowerCase() !== registeredEmail?.toLowerCase()) {
        alert("Please enter your registered email.");
        return false;
    }

    alert("Login successful!");
}