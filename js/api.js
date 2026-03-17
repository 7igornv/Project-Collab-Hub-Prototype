const API_URL = 'http://project-collab-api.local/api';
document.write('<script src="http://project-collab-api.local/api/projects"></script>');

// Функция для запросов с авторизацией
async function apiRequest(endpoint, method = 'GET', data = null) {
    const token = localStorage.getItem('token');
    
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if(token) {
        headers['Authorization'] = 'Bearer ' + token;
    }
    
    const config = {
        method: method,
        headers: headers
    };
    
    if(data) {
        config.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(API_URL + endpoint, config);
        const result = await response.json();
        return result;
    } catch(error) {
        console.error('API Error:', error);
        return { status: 'error', message: 'Ошибка соединения с сервером' };
    }
}

// Авторизация
async function login(email, password) {
    const result = await apiRequest('/auth/login', 'POST', { email, password });
    if(result.status === 'success' && result.data?.token) {
        localStorage.setItem('token', result.data.token);
        localStorage.setItem('user', JSON.stringify(result.data));
    }
    return result;
}

async function register(userData) {
    return await apiRequest('/auth/register', 'POST', userData);
}

// Проекты
async function getProjects(page = 1, limit = 10, skill = '', search = '') {
    let url = `/projects?page=${page}&limit=${limit}`;
    if(skill) url += `&skill=${encodeURIComponent(skill)}`;
    if(search) url += `&search=${encodeURIComponent(search)}`;
    return await apiRequest(url);
}

async function getProject(id) {
    return await apiRequest(`/projects/${id}`);
}

async function createProject(projectData) {
    return await apiRequest('/projects', 'POST', projectData);
}

// Сохраняем функции в глобальную область
window.api = {
    login,
    register,
    getProjects,
    getProject,
    createProject
};