
"use strict"

// for show / hide password
// Call: createpassword('input-id', anchorElement)
let createpassword = (type, ele) => {
    let input = document.getElementById(type);
    if (!input) return;

    // Toggle input type
    input.type = input.type === "password" ? "text" : "password";

    // Always target the first actual element child (skip whitespace text nodes)
    let icon = ele.children[0];
    if (!icon) return;

    // Determine current visible state
    let isText = input.type === "text";

    // Remove both possible eye states, then add the correct one
    icon.classList.remove("ri-eye-line", "ri-eye-off-line");
    icon.classList.add(isText ? "ri-eye-line" : "ri-eye-off-line");
};
