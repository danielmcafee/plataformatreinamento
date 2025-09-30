// JavaScript para a Plataforma de Treinamento

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers do Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Animações de entrada
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    // Observar elementos para animação
    document.querySelectorAll('.card, .stats-card').forEach(el => {
        observer.observe(el);
    });

    // Validação de formulários
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Confirmação de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este item?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Progress bar animation
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.getAttribute('aria-valuenow');
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width + '%';
        }, 500);
    });

    // Video player controls
    const videoContainers = document.querySelectorAll('.video-container');
    videoContainers.forEach(container => {
        const iframe = container.querySelector('iframe');
        if (iframe) {
            // Adicionar controles personalizados se necessário
            iframe.addEventListener('load', function() {
                console.log('Video carregado:', iframe.src);
            });
        }
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.course-card');
            
            cards.forEach(card => {
                const title = card.querySelector('h5').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const preview = document.getElementById('filePreview');
                if (preview) {
                    preview.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-file me-2"></i>
                            Arquivo selecionado: ${file.name}
                            <br>
                            <small>Tamanho: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        </div>
                    `;
                }
            }
        });
    });

    // Questionário interativo
    const questionForms = document.querySelectorAll('.question-form');
    questionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const questionId = this.dataset.questionId;
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
            submitBtn.disabled = true;
            
            // Simular envio (substituir por AJAX real)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Mostrar resultado
                const resultDiv = document.createElement('div');
                resultDiv.className = 'alert alert-success mt-3';
                resultDiv.innerHTML = '<i class="fas fa-check me-2"></i>Resposta enviada com sucesso!';
                this.appendChild(resultDiv);
                
                // Remover formulário
                this.style.display = 'none';
            }, 2000);
        });
    });
});

// Função para extrair ID do YouTube
function extractYouTubeId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

// Função para gerar embed do YouTube
function generateYouTubeEmbed(url) {
    const videoId = extractYouTubeId(url);
    if (videoId) {
        return `https://www.youtube.com/embed/${videoId}`;
    }
    return url;
}

// Função para formatar duração em minutos
function formatDuration(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
        return `${hours}h ${mins}min`;
    }
    return `${mins}min`;
}

// Função para mostrar loading
function showLoading(element) {
    element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Carregando...';
    element.disabled = true;
}

// Função para esconder loading
function hideLoading(element, originalText) {
    element.innerHTML = originalText;
    element.disabled = false;
}
