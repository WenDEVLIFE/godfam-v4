/**
 * Enhanced Searchable Select
 * vanilla JS implementation
 */

function setupSearchableSelect(inputId, resultsId, hiddenInputId, data) {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);
    const hidden = document.getElementById(hiddenInputId);
    
    if (!input || !results) return;

    input.addEventListener('focus', () => {
        renderResults(input.value);
    });

    input.addEventListener('input', () => {
        renderResults(input.value);
    });

    document.addEventListener('click', (e) => {
        if (e.target !== input && !results.contains(e.target)) {
            results.classList.add('d-none');
        }
    });

    function renderResults(query) {
        const filtered = data.filter(item => 
            item.name.toLowerCase().includes(query.toLowerCase()) || 
            item.id.toString().includes(query)
        ).slice(0, 10);

        if (filtered.length === 0) {
            results.innerHTML = '<div class="search-result-item text-muted">No members found</div>';
        } else {
            results.innerHTML = filtered.map(item => `
                <div class="search-result-item" data-id="${item.id}" data-name="${item.name}">
                    <strong>${item.name}</strong> <span class="small text-muted">ID: ${item.id}</span>
                </div>
            `).join('');
        }
        
        results.classList.remove('d-none');

        // Add click listeners to items
        results.querySelectorAll('.search-result-item').forEach(el => {
            el.addEventListener('click', () => {
                const id = el.dataset.id;
                const name = el.dataset.name;
                if (id) {
                    input.value = name;
                    hidden.value = id;
                    results.classList.add('d-none');
                    // Custom callback if needed
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
    }
}
