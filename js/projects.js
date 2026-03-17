// Отображение проектов на главной
function displayProjects() {
    const projectsList = document.getElementById('projects-list');
    if (!projectsList) return;

    const projects = getProjects();
    
    projectsList.innerHTML = projects.map(project => `
        <div class="project-card">
            <h3 class="project-title">${project.title}</h3>
            <p>${project.description.substring(0, 100)}...</p>
            <p><strong>Бюджет:</strong> ${project.budget} ₽</p>
            <p><strong>Заказчик:</strong> ${project.author}</p>
            <div class="project-skills">
                ${project.skills.map(skill => `<span class="skill-tag">${skill}</span>`).join('')}
            </div>
            <button class="btn btn-outline" onclick="showProjectModal(${project.id})">Подробнее</button>
        </div>
    `).join('');
}

// Динамическая фильтрация (для страницы проектов)
function setupProjectFilters() {
    const skillFilter = document.getElementById('skill-filter');
    const searchInput = document.getElementById('search-projects');
    
    if (skillFilter) {
        skillFilter.addEventListener('change', () => {
            const filtered = filterProjects(skillFilter.value, searchInput?.value || '');
            updateProjectsList(filtered);
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const filtered = filterProjects(skillFilter?.value || '', searchInput.value);
            updateProjectsList(filtered);
        });
    }
}

function updateProjectsList(projects) {
    const projectsList = document.getElementById('projects-list');
    if (!projectsList) return;
    
    projectsList.innerHTML = projects.map(project => `
        <div class="project-card">
            <h3 class="project-title">${project.title}</h3>
            <p>${project.description.substring(0, 100)}...</p>
            <p><strong>Бюджет:</strong> ${project.budget} ₽</p>
            <div class="project-skills">
                ${project.skills.map(skill => `<span class="skill-tag">${skill}</span>`).join('')}
            </div>
        </div>
    `).join('');
}

// Запускаем при загрузке страницы
document.addEventListener('DOMContentLoaded', displayProjects);