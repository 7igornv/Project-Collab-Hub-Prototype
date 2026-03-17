// Состояние доски
let boardState = {
    'todo': [],
    'in-progress': [],
    'review': [],
    'done': []
};

// Ключ для localStorage
const BOARD_STORAGE_KEY = 'kanban_board_project_1';

// Инициализация
function initBoard() {
    // Загружаем сохраненное состояние или используем начальное
    const saved = localStorage.getItem(BOARD_STORAGE_KEY);
    if (saved) {
        boardState = JSON.parse(saved);
    } else {
        // Начальные задачи для демо
        boardState = {
            'todo': [
                { id: 1, title: 'Создать макет главной', priority: 'high', description: 'Сделать в Figma' },
                { id: 2, title: 'Написать бэкенд', priority: 'medium', description: 'API для проектов' }
            ],
            'in-progress': [
                { id: 3, title: 'Верстка шапки', priority: 'medium', description: 'Адаптивная шапка' }
            ],
            'review': [],
            'done': [
                { id: 4, title: 'Настройка проекта', priority: 'low', description: 'Инициализация репозитория' }
            ]
        };
    }
    
    renderBoard();
}

// Сохранение состояния
function saveBoard() {
    localStorage.setItem(BOARD_STORAGE_KEY, JSON.stringify(boardState));
}

// Рендер доски
function renderBoard() {
    const board = document.getElementById('kanban-board');
    if (!board) return;
    
    const columns = [
        { id: 'todo', title: 'Нужно сделать' },
        { id: 'in-progress', title: 'В работе' },
        { id: 'review', title: 'На проверке' },
        { id: 'done', title: 'Готово' }
    ];
    
    board.innerHTML = columns.map(column => `
        <div class="kanban-column" data-column="${column.id}">
            <h3>${column.title}</h3>
            <div class="tasks-container" data-column="${column.id}">
                ${renderTasks(boardState[column.id] || [])}
            </div>
            <button class="add-task-btn" data-column="${column.id}">+ Добавить задачу</button>
        </div>
    `).join('');
    
    // Добавляем обработчики drag-and-drop
    setupDragAndDrop();
    
    // Обработчики для кнопок добавления
    document.querySelectorAll('.add-task-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const column = e.target.dataset.column;
            openTaskModal(column);
        });
    });
}

function renderTasks(tasks) {
    return tasks.map(task => `
        <div class="task-card" draggable="true" data-task-id="${task.id}">
            <span class="task-priority priority-${task.priority}">
                ${task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
            </span>
            <div style="font-weight: bold; margin-bottom: 5px;">${task.title}</div>
            <div style="font-size: 12px; color: #666;">${task.description || ''}</div>
        </div>
    `).join('');
}

// Drag and Drop
function setupDragAndDrop() {
    const tasks = document.querySelectorAll('.task-card');
    const containers = document.querySelectorAll('.tasks-container');
    
    tasks.forEach(task => {
        task.addEventListener('dragstart', handleDragStart);
        task.addEventListener('dragend', handleDragEnd);
    });
    
    containers.forEach(container => {
        container.addEventListener('dragover', handleDragOver);
        container.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    e.target.classList.add('dragging');
    e.dataTransfer.setData('text/plain', e.target.dataset.taskId);
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDrop(e) {
    e.preventDefault();
    
    const taskId = e.dataTransfer.getData('text/plain');
    const targetColumn = e.target.closest('.tasks-container')?.dataset.column;
    
    if (!taskId || !targetColumn) return;
    
    // Ищем задачу во всех колонках
    let taskData = null;
    let sourceColumn = null;
    
    for (const [col, tasks] of Object.entries(boardState)) {
        const found = tasks.find(t => t.id == taskId);
        if (found) {
            taskData = found;
            sourceColumn = col;
            break;
        }
    }
    
    if (taskData && sourceColumn && sourceColumn !== targetColumn) {
        // Удаляем из исходной колонки
        boardState[sourceColumn] = boardState[sourceColumn].filter(t => t.id != taskId);
        
        // Добавляем в целевую
        boardState[targetColumn].push(taskData);
        
        // Сохраняем и перерисовываем
        saveBoard();
        renderBoard();
    }
}

// Модальное окно для создания задачи
function openTaskModal(column) {
    const modal = document.getElementById('task-modal');
    const form = document.getElementById('task-form');
    
    modal.classList.add('active');
    
    // Обработчик отправки формы
    form.onsubmit = (e) => {
        e.preventDefault();
        
        const newTask = {
            id: Date.now(),
            title: document.getElementById('task-title').value,
            description: document.getElementById('task-desc').value,
            priority: document.getElementById('task-priority').value
        };
        
        boardState[column].push(newTask);
        saveBoard();
        renderBoard();
        
        modal.classList.remove('active');
        form.reset();
    };
    
    // Закрытие модалки
    document.querySelector('.modal-close').onclick = () => {
        modal.classList.remove('active');
        form.reset();
    };
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            form.reset();
        }
    };
}

// Запуск
document.addEventListener('DOMContentLoaded', initBoard);