// Валидация email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Валидация пароля (минимум 6 символов)
function validatePassword(password) {
    return password.length >= 6;
}

// Валидация формы регистрации
function setupRegistrationValidation() {
    const form = document.getElementById('register-form');
    if (!form) return;

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm-password');

    // Показываем ошибки в реальном времени
    emailInput.addEventListener('input', () => {
        const error = document.getElementById('email-error');
        if (!validateEmail(emailInput.value)) {
            error.textContent = 'Введите корректный email';
            emailInput.classList.add('error');
        } else {
            error.textContent = '';
            emailInput.classList.remove('error');
        }
    });

    passwordInput.addEventListener('input', () => {
        const error = document.getElementById('password-error');
        if (!validatePassword(passwordInput.value)) {
            error.textContent = 'Пароль должен быть не менее 6 символов';
            passwordInput.classList.add('error');
        } else {
            error.textContent = '';
            passwordInput.classList.remove('error');
        }
    });

    confirmInput.addEventListener('input', () => {
        const error = document.getElementById('confirm-error');
        if (confirmInput.value !== passwordInput.value) {
            error.textContent = 'Пароли не совпадают';
            confirmInput.classList.add('error');
        } else {
            error.textContent = '';
            confirmInput.classList.remove('error');
        }
    });

    // Проверка перед отправкой
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const isValid = validateEmail(emailInput.value) && 
                       validatePassword(passwordInput.value) &&
                       passwordInput.value === confirmInput.value;
        
        if (isValid) {
            alert('Форма валидна! (в учебных целях)');
            // Здесь была бы отправка на сервер
        } else {
            alert('Исправьте ошибки в форме');
        }
    });
}

// Валидация формы создания проекта
function setupProjectValidation() {
    const form = document.getElementById('create-project-form');
    if (!form) return;

    const titleInput = document.getElementById('project-title');
    const budgetInput = document.getElementById('project-budget');

    titleInput.addEventListener('input', () => {
        const error = document.getElementById('title-error');
        if (titleInput.value.length < 5) {
            error.textContent = 'Название должно быть не менее 5 символов';
            titleInput.classList.add('error');
        } else {
            error.textContent = '';
            titleInput.classList.remove('error');
        }
    });

    budgetInput.addEventListener('input', () => {
        const error = document.getElementById('budget-error');
        if (budgetInput.value && budgetInput.value < 1000) {
            error.textContent = 'Бюджет не может быть менее 1000 ₽';
            budgetInput.classList.add('error');
        } else {
            error.textContent = '';
            budgetInput.classList.remove('error');
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const newProject = {
            title: titleInput.value,
            description: document.getElementById('project-description').value,
            budget: budgetInput.value,
            skills: Array.from(document.querySelectorAll('input[name="skills"]:checked')).map(cb => cb.value),
            author: 'Текущий пользователь', // в реальности из сессии
            created: new Date().toISOString().split('T')[0]
        };

        addProject(newProject);
        alert('Проект создан! (сохранён в localStorage)');
        window.location.href = 'index.html';
    });
}

// Запускаем при загрузке
document.addEventListener('DOMContentLoaded', () => {
    setupRegistrationValidation();
    setupProjectValidation();
});