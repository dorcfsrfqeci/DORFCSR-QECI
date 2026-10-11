const loginForm = document.getElementById('loginForm');
  const passwordInput = document.getElementById('password');
  const toggleBtn = document.getElementById('togglePassword');

  // 1. Show/Hide Password Toggle
  toggleBtn.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    // Toggle the icon
    const icon = toggleBtn.querySelector('i');
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
  });

  // 2. Simple Login Validation - check if fields are filled
  loginForm.addEventListener('submit', (e) => {
    const username = document.getElementById('username').value.trim();
    const password = passwordInput.value.trim();

    // Validate that fields are not empty
    if (username === "" || password === "") {
      e.preventDefault(); // Prevent form submission if validation fails
      alert("Please fill out all fields.");
      return;
    }

    // Allow form to submit normally to PHP backend for authentication
    // PHP will handle the redirect after successful login
  });
