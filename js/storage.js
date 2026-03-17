// Имитация базы данных в localStorage
const STORAGE_KEYS = {
    PROJECTS: 'collab_projects',
    TASKS: 'collab_tasks'
};

// Начальные данные (если в localStorage пусто)
const INITIAL_PROJECTS = [
    {
        id: 1,
        title: 'Разработка интернет-магазина',
        description: 'Требуется разработать интернет-магазин на PHP',
        budget: 50000,
        skills: ['PHP', 'JavaScript', 'MySQL'],
        author: 'Иван Петров',
        created: '2026-03-01'
    },
    {
        id: 2,
        title: 'Дизайн лендинга',
        description: 'Создать современный дизайн для посадочной страницы',
        budget: 15000,
        skills: ['UI/UX', 'Figma', 'Photoshop'],
        author: 'Анна Смирнова',
        created: '2026-03-02'
    },
    {
        id: 3,
        title: 'Мобильное приложение',
        description: 'Разработка приложения для доставки еды',
        budget: 80000,
        skills: ['React Native', 'Node.js', 'MongoDB'],
        author: 'Петр Сидоров',
        created: '2026-03-03'
    }
];

// Инициализация хранилища
function initStorage() {
    if (!localStorage.getItem(STORAGE_KEYS.PROJECTS)) {
        localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(INITIAL_PROJECTS));
    }
}

// Получить все проекты
function getProjects() {
    const projects = localStorage.getItem(STORAGE_KEYS.PROJECTS);
    return projects ? JSON.parse(projects) : [];
}

// Добавить проект
function addProject(project) {
    const projects = getProjects();
    project.id = Date.now(); // простой способ получить уникальный ID
    projects.push(project);
    localStorage.setItem(STORAGE_KEYS.PROJECTS, JSON.stringify(projects));
    return project;
}

// Фильтрация проектов
function filterProjects(skill = '', search = '') {
    const projects = getProjects();
    return projects.filter(project => {
        const matchesSkill = !skill || project.skills.includes(skill);
        const matchesSearch = !search || 
            project.title.toLowerCase().includes(search.toLowerCase()) ||
            project.description.toLowerCase().includes(search.toLowerCase());
        return matchesSkill && matchesSearch;
    });
}

// Запускаем инициализацию
initStorage();