<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <h1 class="text-3xl font-bold text-center text-blue-600 mb-8">User Management</h1>

        <form id="userForm" class="bg-white shadow-md rounded-lg p-6 mb-8" novalidate>
            <input type="hidden" id="userId" />

            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-semibold mb-2">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" placeholder="Enter name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
                <div class="text-red-600 text-sm mt-1" id="nameError"></div>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-semibold mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" id="email" name="email" placeholder="Enter email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
                <div class="text-red-600 text-sm mt-1" id="emailError"></div>
            </div>

            <div class="mb-6">
                <label for="age" class="block text-gray-700 font-semibold mb-2">
                    Age <span class="text-red-500">*</span>
                </label>
                <input type="number" id="age" name="age" placeholder="Enter age" min="1"
                    max="120" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
                <div class="text-red-600 text-sm mt-1" id="ageError"></div>
            </div>

            <div class="flex items-center">
                <button type="submit" id="submitBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-blue-300 disabled:cursor-not-allowed">
                    Add User
                </button>
                <button type="button" id="cancelBtn"
                    class="hidden ml-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Cancel
                </button>
            </div>
        </form>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto" id="usersTable">
                    <thead>
                        <tr class="bg-blue-600 text-white">
                            <th class="px-6 py-3 text-left font-medium">Name</th>
                            <th class="px-6 py-3 text-left font-medium">Email</th>
                            <th class="px-6 py-3 text-left font-medium">Age</th>
                            <th class="px-6 py-3 text-left font-medium w-[140px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersList" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = "http://127.0.0.1:8000/api";
        const LOG_ENDPOINT = `${API_BASE_URL}/log`;

        const userForm = document.getElementById('userForm');
        const userIdInput = document.getElementById('userId');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const ageInput = document.getElementById('age');
        const nameError = document.getElementById('nameError');
        const emailError = document.getElementById('emailError');
        const ageError = document.getElementById('ageError');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const usersList = document.getElementById('usersList');

        // Logging function to record API activities
        async function logActivity(method, url, status, message) {
            try {
                const timestamp = new Date().toISOString();
                const logData = {
                    timestamp,
                    method,
                    url,
                    status,
                    message
                };

                console.log('Logging activity:', logData);

                await fetch(LOG_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(logData)
                });
            } catch (error) {
                console.error('Failed to log activity:', error);
            }
        }

        // Wrapper for fetch to include logging
        async function fetchWithLogging(url, options = {}) {
            const method = options.method || 'GET';
            let status = 'error';
            let message = '';

            try {
                const response = await fetch(url, options);
                const responseData = await response.json();

                status = response.ok ? 'success' : 'error';
                message = response.ok ?
                    'Operation completed successfully' :
                    (responseData.message || 'Operation failed');

                // Log the activity
                await logActivity(method, url, status, message);

                // Jika response tidak ok, lempar error
                if (!response.ok) {
                    const error = new Error(message);
                    error.response = response;
                    error.data = responseData;
                    throw error;
                }

                return {
                    response,
                    data: responseData
                };
            } catch (error) {
                // Tangani error validasi
                if (error.response && error.response.status === 422 && error.data && error.data.errors) {
                    throw error; // Lempar error validasi untuk ditangani di tempat lain
                }

                // Jika error lain, log dan lempar error
                if (status === 'error' && !message) {
                    message = error.message;
                    await logActivity(method, url, status, message);
                }
                throw error;
            }
        }

        function validateName(name) {
            return name.trim().length >= 2;
        }

        function validateEmail(email) {
            return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.trim());
        }

        function validateAge(age) {
            const num = Number(age);
            return Number.isInteger(num) && num >= 1 && num <= 120;
        }

        function clearErrors() {
            nameError.textContent = '';
            emailError.textContent = '';
            ageError.textContent = '';
        }

        function showErrors(errors) {
            nameError.textContent = errors.name || '';
            emailError.textContent = errors.email || '';
            ageError.textContent = errors.age || '';
        }

        async function fetchUsers() {
            try {
                usersList.innerHTML =
                    '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading users...</td></tr>';

                const {
                    data
                } = await fetchWithLogging(`${API_BASE_URL}/users`);
                console.log('Users data:', data);

                const users = Array.isArray(data) ? data : (data.data || []);
                renderUsers(users);
            } catch (error) {
                console.error('Error fetching users:', error);
                usersList.innerHTML =
                    `<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error: ${error.message}</td></tr>`;
            }
        }

        function renderUsers(users) {
            if (!Array.isArray(users) || users.length === 0) {
                usersList.innerHTML =
                    '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>';
                return;
            }

            usersList.innerHTML = users.map((user, index) => {
                const rowClass = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';

                return `
                <tr class="${rowClass} hover:bg-gray-100 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">${escapeHtml(user.name || '')}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${escapeHtml(user.email || '')}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${escapeHtml(user.age != null ? user.age.toString() : '')}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm mr-2 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                            data-id="${user.id}"
                            aria-label="Edit user ${escapeHtml(user.name || '')}"
                            type="button">
                            Edit
                        </button>
                        <button
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                            data-id="${user.id}"
                            aria-label="Delete user ${escapeHtml(user.name || '')}"
                            type="button">
                            Delete
                        </button>
                    </td>
                </tr>
                `;
            }).join('');
        }

        function resetForm() {
            userIdInput.value = '';
            nameInput.value = '';
            emailInput.value = '';
            ageInput.value = '';
            submitBtn.textContent = 'Add User';
            cancelBtn.classList.add('hidden');
            clearErrors();
        }

        function fillForm(user) {
            console.log('Filling form with user data:', user);
            userIdInput.value = user.id;
            nameInput.value = user.name || '';
            emailInput.value = user.email || '';
            ageInput.value = user.age != null ? user.age : '';
            submitBtn.textContent = 'Update User';
            cancelBtn.classList.remove('hidden');
            clearErrors();

            userForm.scrollIntoView({
                behavior: 'smooth'
            });
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';

            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function showToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.className =
                `fixed bottom-4 right-4 px-4 py-2 rounded-md text-white ${isError ? 'bg-red-600' : 'bg-green-600'} shadow-lg z-50 transition-opacity duration-300`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        userForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const id = userIdInput.value;
            const name = nameInput.value.trim();
            const email = emailInput.value.trim();
            const age = ageInput.value.trim();

            const errors = {};
            if (!validateName(name)) {
                errors.name = 'Name must be at least 2 characters.';
            }
            if (!validateEmail(email)) {
                errors.email = 'Email must be valid.';
            }
            if (!validateAge(age)) {
                errors.age = 'Age must be a number between 1 and 120.';
            }

            if (Object.keys(errors).length > 0) {
                showErrors(errors);
                return;
            }

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = id ? 'Updating...' : 'Adding...';

                const userData = {
                    name,
                    email,
                    age: Number(age)
                };

                if (id) {
                    const url = `${API_BASE_URL}/users/${id}`;
                    await fetchWithLogging(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(userData),
                    });
                    showToast('User updated successfully!');
                } else {
                    const url = `${API_BASE_URL}/users`;
                    await fetchWithLogging(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(userData),
                    });
                    showToast('User added successfully!');
                }

                await fetchUsers();
                resetForm();
            } catch (error) {
                console.error('Form submission error:', error);

                // Tangani error validasi
                if (error.response && error.response.status === 422 && error.data && error.data.errors) {
                    showErrors(error.data.errors);
                } else {
                    showToast(error.message, true);
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = id ? 'Update User' : 'Add User';
            }
        });

        cancelBtn.addEventListener('click', () => {
            resetForm();
        });

        document.addEventListener('click', async (e) => {
            if (e.target.matches('button[aria-label^="Edit user"]')) {
                const id = e.target.getAttribute('data-id');
                if (!id) return;

                try {
                    console.log(`Fetching user data for ID: ${id}`);
                    const url = `${API_BASE_URL}/users/${id}`;
                    const {
                        data
                    } = await fetchWithLogging(url);

                    console.log('User data received:', data);
                    const userData = data.data || data;
                    fillForm(userData);
                } catch (error) {
                    console.error('Error fetching user for edit:', error);
                    showToast(error.message, true);
                }
            }

            if (e.target.matches('button[aria-label^="Delete user"]')) {
                const id = e.target.getAttribute('data-id');
                if (!id) return;

                if (!confirm('Are you sure you want to delete this user?')) {
                    return;
                }

                try {
                    e.target.disabled = true;
                    e.target.textContent = 'Deleting...';

                    const url = `${API_BASE_URL}/users/${id}`;
                    await fetchWithLogging(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    showToast('User deleted successfully!');
                    await fetchUsers();
                } catch (error) {
                    console.error('Error deleting user:', error);
                    showToast(error.message, true);
                    e.target.disabled = false;
                    e.target.textContent = 'Delete';
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchUsers();
        });
    </script>
</body>

</html>
