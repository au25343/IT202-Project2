
let firstNameField = '';
let lastNameField = '';
let employeeIdField = '';
let passwordField = '';
let phoneNumberField = '';
let emailField = '';
let emailConfirmField = '';
let transactionField = '';

(() => {
    firstNameField = document.getElementById("firstName");
    lastNameField = document.getElementById("lastName");
    employeeIdField = document.getElementById("employeeID");
    passwordField = document.getElementById("password");
    phoneNumberField = document.getElementById("phoneNumber");
    emailField = document.getElementById("email");
    emailConfirmField = document.getElementById("emailConfirm");
    transactionField = document.getElementById("transaction");
})();

// Show or hide password
function toggleEye() {
    const eye = document.getElementById("eye");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        eye.textContent = "🙈";
    } else {
        passwordField.type = "password";
        eye.textContent = "👁️";
    }
}

// Validate login form
const validateForm = () => {

    // Regular expressions
    const firstNameRegex = /^[A-Za-z]+$/;
    const lastNameRegex = /^[A-Za-z]+$/;

    // Maximum 7 characters, starts with special character,
    // contains uppercase letter and number
    const passwordRegex = /^(?=.*[A-Z])(?=.*\d)[^A-Za-z0-9].{0,6}$/;

    // Exactly 4 digits
    const employeeIdRegex = /^\d{4}$/;

    // 10 digit phone number with extension
    const phoneNumberRegex = /^\d{3}[-\s]?\d{3}[-\s]?\d{4}\s*(ext\.?|x)\s*\d+$/i;

    // Email with @, period and 2-5 letter ending
    const emailRegex = /^\S+@\S+\.[A-Za-z]{2,5}$/;

    // First Name
    if (firstNameField.value.trim() === "") {
        alert("First name required.");
        firstNameField.focus();
        return false;
    }

    if (!firstNameRegex.test(firstNameField.value)) {
        alert("First Name requires letters only.");
        firstNameField.focus();
        return false;
    }

    // Last Name
    if (lastNameField.value.trim() === "") {
        alert("Last name required.");
        lastNameField.focus();
        return false;
    }

    if (!lastNameRegex.test(lastNameField.value)) {
        alert("Last Name requires letters only.");
        lastNameField.focus();
        return false;
    }

    // Password
    if (passwordField.value.trim() === "") {
        alert("Password required.");
        passwordField.focus();
        return false;
    }

    if (!passwordRegex.test(passwordField.value)) {
        alert("Password must start with a special character, contain an uppercase letter and a number, and have a maximum of 7 characters.");
        passwordField.focus();
        return false;
    }

    // Employee ID
    if (employeeIdField.value.trim() === "") {
        alert("Employee ID is required.");
        employeeIdField.focus();
        return false;
    }

    if (!employeeIdRegex.test(employeeIdField.value)) {
        alert("Employee ID must contain exactly 4 digits.");
        employeeIdField.focus();
        return false;
    }

    // Phone Number
    if (phoneNumberField.value.trim() === "") {
        alert("Phone number required.");
        phoneNumberField.focus();
        return false;
    }

    if (!phoneNumberRegex.test(phoneNumberField.value)) {
        alert("Phone number must contain 10 digits and an extension, for example 111-222-3333 ext 1.");
        phoneNumberField.focus();
        return false;
    }

    // Email Confirmation
    if (emailConfirmField.checked) {

        if (emailField.value.trim() === "") {
            alert("Email is required when confirmation is selected.");
            emailField.focus();
            return false;
        }

        if (!emailRegex.test(emailField.value)) {
            alert("Email must contain @, a period, and a 2-5 letter domain ending.");
            emailField.focus();
            return false;
        }
    }

    // Transaction
    if (transactionField.value === "") {
        alert("Please select a transaction from the drop-down menu.");
        transactionField.focus();
        return false;
    }

    return true;
};

// Submit form
document.getElementById("movingForm").addEventListener("submit", (e) => {

    if (!validateForm()) {
        e.preventDefault();
    }

    // PHP will verify login using MySQL
});
