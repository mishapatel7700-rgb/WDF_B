
        function register() {
            let name = document.getElementById("name").value;
            let mobile = document.getElementById("mobile").value;
            let city = document.getElementById("city").value;
            let email = document.getElementById("email").value;
            let password = document.getElementById("password").value;

            if (!name || !mobile || !city || !email || !password) {
                alert("Please enter all details.");
                return;
            }

            localStorage.setItem("registeredName", name);
            localStorage.setItem("registeredMobile", mobile);
            localStorage.setItem("registeredCity", city);
            localStorage.setItem("registeredEmail", email);
            localStorage.setItem("registeredPassword", password);

            alert("Registration successful! Your email has been registered.");

            window.location.href = "Login.html";
        }